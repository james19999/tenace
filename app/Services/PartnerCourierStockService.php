<?php

namespace App\Services;

use App\Models\Orders\Order;
use App\Models\PartnerCourierStockIssueLine;
use App\Models\PartnerProductStock;
use App\Models\ProductCountryPrice;
use App\Models\Product;
use App\Models\StockAuditLog;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PartnerCourierStockService
{
    public function hasWarehouseForCountry(string $country): bool
    {
        return $this->warehouseForCountry($country) !== null;
    }

    /** Cumulative quantities transferred from warehouses to one partner. */
    public function suppliedQuantities(User $partner, iterable $productIds): \Illuminate\Support\Collection
    {
        $ids = collect($productIds)->map(fn ($id) => (int) $id)->all();

        return DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->where('sm.status', 'posted')
            ->where('sm.source_type', 'partner_stock_replenishment')
            ->where('sm.source_reference', (string) $partner->id)
            ->whereIn('sml.product_id', $ids)
            ->groupBy('sml.product_id')
            ->selectRaw('sml.product_id, SUM(CASE WHEN sml.quantity_delta < 0 THEN -sml.quantity_delta ELSE 0 END) as quantity')
            ->pluck('quantity', 'sml.product_id');
    }

    /** Move quantities from the country's warehouse into one partner's stock. */
    public function replenish(User $partner, User $actor, array $quantities): void
    {
        $country = strtoupper((string) $partner->country_code);
        $quantities = collect($quantities)->mapWithKeys(fn ($quantity, $id) => [(int) $id => (int) $quantity])
            ->filter(fn ($quantity) => $quantity > 0)->all();
        if ($quantities === []) return;

        // The legacy reconciliation is safe only while a single warehouse exists;
        // it transfers the old catalogue balances into its stock ledger once.
        if (StockLocation::query()->where('type', 'warehouse')->where('is_active', true)->count() === 1) {
            app(StockLegacyWarehouseReconciliationService::class)->reconcileSingleWarehouse();
        }

        $warehouse = $this->warehouseForCountry($country);
        if (! $warehouse) {
            throw new \DomainException('Aucun entrepôt actif n’est associé au pays '.$country.'. Associe le pays à son entrepôt dans Contrôle des stocks.');
        }

        DB::transaction(function () use ($partner, $actor, $quantities, $country, $warehouse): void {
            $lockedWarehouse = StockLocation::query()->whereKey($warehouse->id)->lockForUpdate()->firstOrFail();
            $settings = app(StockModuleSettingsService::class)->rules();
            $legacyCatalogueBalance = empty($settings['legacy_catalogue_stock_migrated_to_shop']);
            $prepared = [];

            foreach ($quantities as $productId => $quantity) {
                $product = Product::query()->lockForUpdate()->findOrFail($productId);
                if (! ProductCountryPrice::query()->where('product_id', $productId)
                    ->where('country_code', $country)->where('is_available', true)->exists()) {
                    throw new \DomainException('Active d’abord ce produit dans le catalogue du pays avant de réapprovisionner son stock.');
                }

                $warehouseBalance = (float) DB::table('stock_movement_lines as sml')
                    ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
                    ->where('sm.status', 'posted')->where('sml.location_id', $lockedWarehouse->id)
                    ->where('sml.product_id', $productId)->sum('sml.quantity_delta');
                if ($legacyCatalogueBalance && StockLocation::query()->where('type', 'warehouse')->where('is_active', true)->count() === 1) {
                    $warehouseBalance = max($warehouseBalance, (float) $product->qt_initial);
                }
                if (! empty($settings['prevent_negative_stock']) && $warehouseBalance < $quantity) {
                    throw new \DomainException('Le stock central est insuffisant pour transférer la quantité demandée de « '.$product->name.' ».');
                }
                if ($legacyCatalogueBalance && StockLocation::query()->where('type', 'warehouse')->where('is_active', true)->count() === 1
                    && (int) $product->qt_initial < $quantity && $warehouseBalance < $quantity) {
                    throw new \DomainException('Le stock central du produit « '.$product->name.' » est insuffisant.');
                }

                $prepared[] = ['product' => $product, 'quantity' => $quantity, 'before' => $warehouseBalance];
            }

            $now = now();
            $movement = StockMovement::query()->create([
                'movement_number' => 'TMP-'.Str::uuid(),
                'type' => 'transfer_shipment',
                'status' => 'posted',
                'source_type' => 'partner_stock_replenishment',
                'source_reference' => (string) $partner->id,
                'occurred_at' => $now,
                'origin' => 'partner',
                'reason' => 'Réapprovisionnement du partenaire '.$partner->name.' · '.$country,
                'created_by' => $actor->id,
                'approved_by' => $actor->id,
                'posted_by' => $actor->id,
                'approved_at' => $now,
                'posted_at' => $now,
            ]);
            $movement->update(['movement_number' => sprintf('ST-MVT-%s-%06d', $now->format('Y'), $movement->id)]);

            foreach ($prepared as $line) {
                $product = $line['product'];
                $quantity = $line['quantity'];
                $partnerStock = PartnerProductStock::query()->where('partner_id', $partner->id)
                    ->where('product_id', $product->id)->where('country_code', $country)->lockForUpdate()->first();
                if (! $partnerStock) {
                    $partnerStock = PartnerProductStock::query()->create([
                        'partner_id' => $partner->id, 'product_id' => $product->id,
                        'country_code' => $country, 'quantity' => 0,
                    ]);
                }
                $partnerStock->increment('quantity', $quantity);

                if ($legacyCatalogueBalance) {
                    $product->qt_initial = max(0, (int) $product->qt_initial - $quantity);
                    $product->save();
                }

                $movement->lines()->create([
                    'product_id' => $product->id,
                    'location_id' => $lockedWarehouse->id,
                    'quantity_delta' => -$quantity,
                    'unit_cost' => $product->price_market,
                    'unit' => 'piece',
                    'notes' => 'Réapprovisionnement partenaire '.$partner->name,
                ]);

                StockAuditLog::query()->create([
                    'user_id' => $actor->id,
                    'actor_role' => $actor->user_type,
                    'action' => 'partner_stock.replenished',
                    'subject_type' => StockMovement::class,
                    'subject_id' => $movement->id,
                    'before_values' => ['warehouse_quantity' => $line['before']],
                    'after_values' => ['partner_id' => $partner->id, 'product_id' => $product->id, 'quantity_added' => $quantity, 'movement_id' => $movement->id],
                    'origin' => 'partner',
                    'result' => 'success',
                ]);
            }
        });
    }

    public function hasForOrder(User $courier, Order $order): bool
    {
        foreach ($order->orderItems->groupBy('product_id') as $productId => $items) {
            $needed = (int) $items->sum('quantity');
            $available = PartnerCourierStockIssueLine::query()->whereHas('issue', fn ($query) => $query
                ->where('courier_id', $courier->id)->where('partner_id', $order->partner_id))
                ->where('product_id', $productId)->sum('quantity_remaining');
            if ($available < $needed) return false;
        }

        return true;
    }

    public function consumeForOrder(User $courier, Order $order): ?string
    {
        $allocations = [];
        foreach ($order->orderItems->groupBy('product_id') as $productId => $items) {
            $needed = (int) $items->sum('quantity');
            $lines = PartnerCourierStockIssueLine::query()->whereHas('issue', fn ($query) => $query
                ->where('courier_id', $courier->id)->where('partner_id', $order->partner_id))
                ->where('product_id', $productId)->where('quantity_remaining', '>', 0)
                ->orderBy('id')->lockForUpdate()->get();
            if ($lines->sum('quantity_remaining') < $needed) {
                return 'Stock insuffisant chez toi pour livrer cette commande. Demande au partenaire de te remettre les produits manquants.';
            }

            foreach ($lines as $line) {
                $used = min($needed, (int) $line->quantity_remaining);
                $allocations[] = [$line, $used];
                $needed -= $used;
                if ($needed === 0) break;
            }
        }

        foreach ($allocations as [$line, $quantity]) {
            $line->decrement('quantity_remaining', $quantity);
        }

        return null;
    }

    public function issue(User $partner, User $courier, User $actor, array $quantities): void
    {
        $country = strtoupper((string) $partner->country_code);
        DB::transaction(function () use ($partner, $courier, $actor, $quantities, $country): void {
            $prepared = [];

            foreach ($quantities as $productId => $quantity) {
                $quantity = (int) $quantity;
                if ($quantity < 1) continue;

                $stock = PartnerProductStock::query()->where('partner_id', $partner->id)->where('product_id', $productId)
                    ->where('country_code', $country)->lockForUpdate()->firstOrFail();
                if (! ProductCountryPrice::query()->where('product_id', $productId)
                    ->where('country_code', $country)->where('is_available', true)->exists()) {
                    throw new \DomainException('Ce produit n’est plus disponible pour ton pays.');
                }
                if ((int) $stock->quantity < $quantity) {
                    throw new \DomainException('Stock partenaire insuffisant pour le produit '.$stock->product_id.'.');
                }

                $prepared[] = ['stock' => $stock, 'product' => Product::query()->findOrFail($productId), 'quantity' => $quantity];
            }

            $issue = \App\Models\PartnerCourierStockIssue::create([
                'partner_id' => $partner->id,
                'courier_id' => $courier->id,
                'issued_by' => $actor->id,
                'country_code' => $country,
                'issued_at' => now(),
            ]);
            foreach ($prepared as $line) {
                $line['stock']->decrement('quantity', $line['quantity']);
                $issue->lines()->create([
                    'product_id' => $line['product']->id,
                    'quantity_issued' => $line['quantity'],
                    'quantity_remaining' => $line['quantity'],
                ]);
            }

            StockAuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_role' => $actor->user_type,
                'action' => 'partner_courier.stock_issued',
                'subject_type' => \App\Models\PartnerCourierStockIssue::class,
                'subject_id' => $issue->id,
                'after_values' => [
                    'partner_id' => $partner->id,
                    'courier_id' => $courier->id,
                    'country_code' => $country,
                    'products' => collect($prepared)->map(fn ($line) => [
                        'product_id' => $line['product']->id,
                        'quantity' => $line['quantity'],
                    ])->values()->all(),
                ],
                'origin' => 'partner',
                'result' => 'success',
            ]);
        });
    }

    private function warehouseForCountry(string $country): ?StockLocation
    {
        $active = StockLocation::query()->where('type', 'warehouse')->where('is_active', true);
        $warehouse = (clone $active)->where('country_code', strtoupper($country))->first();
        if ($warehouse) return $warehouse;

        $unassigned = (clone $active)->whereNull('country_code')->limit(2)->get();
        if ($unassigned->count() === 1 && (clone $active)->count() === 1) return $unassigned->first();

        return null;
    }
}
