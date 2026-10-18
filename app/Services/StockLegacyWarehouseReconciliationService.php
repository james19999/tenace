<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAuditLog;
use App\Models\StockLocation;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockLegacyWarehouseReconciliationService
{
    /**
     * Assign positive legacy catalogue balances to the sole active warehouse.
     * No physical quantities are guessed when more than one warehouse exists.
     */
    public function reconcileSingleWarehouse(): int
    {
        // In production the legacy catalogue balance is migrated into the shop once.
        // Do not reinterpret that compatibility value as warehouse stock afterward.
        $rules = app(StockModuleSettingsService::class)->rules();
        if (! empty($rules['legacy_catalogue_stock_migrated_to_shop'])) {
            return 0;
        }

        $warehouses = StockLocation::query()
            ->where('type', 'warehouse')
            ->where('is_active', true)
            ->limit(2)
            ->get(['id', 'name']);

        if ($warehouses->count() !== 1) {
            return 0;
        }

        $warehouse = $warehouses->first();

        return DB::transaction(function () use ($warehouse): int {
            $products = Product::query()->orderBy('id')->lockForUpdate()->get(['id', 'name', 'qt_initial', 'price_market']);
            $locatedBalances = DB::table('stock_movement_lines as sml')
                ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
                ->where('sm.status', 'posted')
                ->where('sml.location_id', $warehouse->id)
                ->select('sml.product_id')
                ->selectRaw('SUM(sml.quantity_delta) as quantity')
                ->groupBy('sml.product_id')
                ->pluck('quantity', 'product_id');
            $deltas = [];

            foreach ($products as $product) {
                $located = (float) ($locatedBalances[$product->id] ?? 0);
                $missing = (float) ($product->qt_initial ?? 0) - $located;

                if ($missing > 0.0005) {
                    $deltas[] = ['product' => $product, 'before' => $located, 'quantity' => $missing];
                }
            }

            if ($deltas === []) {
                return 0;
            }

            $now = now();
            $movement = StockMovement::query()->create([
                'movement_number' => 'TMP-'.Str::uuid(),
                'type' => 'opening_balance',
                'status' => 'posted',
                'source_type' => 'legacy_reconciliation',
                'source_reference' => 'warehouse-'.$warehouse->id,
                'occurred_at' => $now,
                'origin' => 'system',
                'reason' => 'Affectation automatique des stocks catalogue historiques au seul entrepôt actif.',
                'notes' => 'Rapprochement idempotent : seules les quantités positives absentes du journal ont été inscrites.',
                'metadata' => ['warehouse_id' => $warehouse->id, 'legacy_reconciliation' => true],
                'posted_at' => $now,
            ]);
            $movement->update(['movement_number' => sprintf('ST-MVT-%s-%06d', $now->format('Y'), $movement->id)]);

            foreach ($deltas as $delta) {
                /** @var Product $product */
                $product = $delta['product'];
                $quantity = $delta['quantity'];
                $movement->lines()->create([
                    'product_id' => $product->id,
                    'location_id' => $warehouse->id,
                    'quantity_delta' => $quantity,
                    'unit_cost' => $product->price_market,
                    'unit' => 'piece',
                    'notes' => 'Stock catalogue historique affecté automatiquement à l’entrepôt.',
                ]);

                StockAuditLog::query()->create([
                    'actor_role' => 'SYSTEM',
                    'action' => 'inventory.legacy_stock_allocated',
                    'subject_type' => Product::class,
                    'subject_id' => $product->id,
                    'before_values' => [
                        'warehouse_quantity' => $delta['before'],
                        'catalogue_quantity' => (float) ($product->qt_initial ?? 0),
                    ],
                    'after_values' => [
                        'warehouse_quantity' => $delta['before'] + $quantity,
                        'quantity_allocated' => $quantity,
                        'warehouse_id' => $warehouse->id,
                        'warehouse_name' => $warehouse->name,
                        'movement_id' => $movement->id,
                    ],
                    'reason' => 'Affectation automatique du stock historique à l’unique entrepôt actif.',
                    'origin' => 'system',
                    'result' => 'success',
                ]);
            }

            return count($deltas);
        });
    }
}
