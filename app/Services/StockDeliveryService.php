<?php

namespace App\Services;

use App\Models\Orders\Order;
use App\Models\Product;
use App\Models\StockAuditLog;
use App\Models\StockLocation;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockDeliveryService
{
    public function __construct(private readonly StockModuleSettingsService $settings)
    {
    }

    /**
     * Updates the legacy warehouse quantity only for warehouse sales and posts the location ledger once.
     * Returns false when the order was already delivered.
     */
    public function deliver(Order $order, int $actorId): bool
    {
        return DB::transaction(function () use ($order, $actorId): bool {
            $lockedOrder = Order::query()->lockForUpdate()->with('orderItems')->findOrFail($order->id);
            if ($lockedOrder->status === 'delivered') {
                return false;
            }

            if (StockMovement::query()->where('order_id', $lockedOrder->id)->where('type', 'sale')->where('status', 'posted')->exists()) {
                throw ValidationException::withMessages(['stock' => 'Une sortie de stock existe déjà pour cette commande. Vérifie son statut avant de poursuivre.']);
            }

            $rules = $this->settings->rules();
            $location = null;
            if (is_numeric($lockedOrder->user_id)) {
                $location = StockLocation::query()->where('responsible_user_id', (int) $lockedOrder->user_id)
                    ->where('type', 'sales_rep')->where('is_active', true)->first();
            }
            if (! $location && ! empty($rules['default_sales_location_id'])) {
                $location = StockLocation::query()->whereKey($rules['default_sales_location_id'])
                    ->where('is_active', true)->whereNotIn('type', ['transit', 'blocked'])->first();
            }

            if ($location && $rules['prevent_negative_stock']) {
                $balances = DB::table('stock_movement_lines as sml')
                    ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
                    ->where('sm.status', 'posted')->where('sml.location_id', $location->id)
                    ->select('sml.product_id')->selectRaw('SUM(sml.quantity_delta) as quantity')
                    ->groupBy('sml.product_id')->pluck('quantity', 'product_id');
                foreach ($lockedOrder->orderItems->groupBy('product_id') as $productId => $items) {
                    $requiredQuantity = $items->sum(fn ($item) => (float) $item->quantity);
                    if ((float) ($balances[$productId] ?? 0) < $requiredQuantity) {
                        $productName = Product::query()->whereKey($productId)->value('name') ?? 'Produit';
                        throw ValidationException::withMessages([
                            'stock' => 'Stock insuffisant à l’emplacement « '.$location->name.' » pour livrer '.$productName.'. Enregistre ou transfère d’abord les quantités physiques.',
                        ]);
                    }
                }
            }

            $movement = null;
            if ($location) {
                $movement = StockMovement::query()->create([
                    'movement_number' => 'TMP-'.Str::uuid(), 'type' => 'sale', 'status' => 'posted',
                    'order_id' => $lockedOrder->id, 'source_type' => 'order_delivery',
                    'source_reference' => $lockedOrder->code, 'occurred_at' => now(), 'origin' => 'order',
                    'reason' => 'Livraison de la commande '.$lockedOrder->code,
                    'created_by' => $actorId, 'approved_by' => $actorId, 'posted_by' => $actorId,
                    'approved_at' => now(), 'posted_at' => now(),
                ]);
                $movement->update(['movement_number' => sprintf('ST-VTE-%s-%06d', now()->format('Y'), $movement->id)]);
            }

            foreach ($lockedOrder->orderItems as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $quantity = (int) $item->quantity;
                // qt_initial is the legacy warehouse balance. A shop sale changes only
                // the shop ledger; decrementing this field as well creates a false gap.
                if (! $location || $location->type === 'warehouse') {
                    $product->qt_initial = (int) $product->qt_initial - $quantity;
                }
                $product->qts_sell = (int) $product->qts_sell + $quantity;
                $product->benefice = ($product->price - $product->price_market) * $product->qts_sell;
                $product->save();

                if ($movement && $quantity !== 0) {
                    $movement->lines()->create([
                        'product_id' => $product->id, 'location_id' => $location->id,
                        'quantity_delta' => -$quantity, 'unit_cost' => $product->price_market,
                        'unit' => 'piece', 'notes' => 'Sortie à la livraison validée',
                    ]);
                }
            }

            $lockedOrder->status = 'delivered';
            $lockedOrder->user_id = $actorId;
            $lockedOrder->status_order = false;
            $lockedOrder->take = false;
            $lockedOrder->save();

            if ($movement) {
                StockAuditLog::query()->create([
                    'user_id' => $actorId, 'actor_role' => auth()->user()?->user_type,
                    'action' => 'order.stock_decremented', 'subject_type' => StockMovement::class,
                    'subject_id' => $movement->id,
                    'after_values' => ['order_id' => $lockedOrder->id, 'location_id' => $location->id, 'line_count' => $lockedOrder->orderItems->count()],
                    'origin' => 'order', 'result' => 'success',
                ]);
            }

            return true;
        });
    }
}
