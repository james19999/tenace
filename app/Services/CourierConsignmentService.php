<?php

namespace App\Services;

use App\Models\CourierConsignmentEvent;
use App\Models\CourierConsignmentLine;
use App\Models\CourierConsignmentRun;
use App\Models\Orders\Order;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\StockAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourierConsignmentService
{
    public function assign(StockLocation $shop, User $courier, array $lines, User $actor): CourierConsignmentRun
    {
        return DB::transaction(function () use ($shop, $courier, $lines, $actor): CourierConsignmentRun {
            StockLocation::query()->whereKey($shop->id)->lockForUpdate()->firstOrFail();
            $courierLocation = StockLocation::query()->firstOrCreate(
                ['code' => 'LIV-'.str_pad((string) $courier->id, 8, '0', STR_PAD_LEFT)],
                ['name' => 'Tournée · '.$courier->name, 'type' => 'courier', 'responsible_user_id' => $courier->id, 'parent_location_id' => $shop->id, 'is_active' => true],
            );
            if (! $courierLocation->is_active || (int) $courierLocation->responsible_user_id !== (int) $courier->id) {
                throw ValidationException::withMessages(['courier' => 'L’emplacement de tournée du livreur est désactivé ou mal affecté.']);
            }
            $run = CourierConsignmentRun::query()->where('courier_user_id', $courier->id)->where('shop_location_id', $shop->id)
                ->whereDate('run_date', today())->where('status', 'open')->lockForUpdate()->first();
            if (! $run) {
                $run = CourierConsignmentRun::query()->create([
                    'reference' => 'TMP-'.Str::uuid(), 'run_date' => today(), 'shop_location_id' => $shop->id,
                    'courier_location_id' => $courierLocation->id, 'courier_user_id' => $courier->id,
                    'status' => 'open', 'assigned_by' => $actor->id,
                ]);
                $run->update(['reference' => sprintf('TOUR-%s-%06d', today()->format('Y'), $run->id)]);
            }
            $movement = $this->movement($run, 'courier_consignment', 'Consignation de produits au livreur '.$courier->name, $actor);
            $eventLines = [];
            foreach ($lines as $input) {
                $product = Product::query()->lockForUpdate()->findOrFail($input['product_id']);
                $quantity = (float) $input['quantity'];
                $available = $this->balance($shop->id, $product->id);
                if ($quantity <= 0 || $available < $quantity) {
                    throw ValidationException::withMessages(['lines' => 'Stock insuffisant pour « '.$product->name.' ». Disponible en boutique : '.rtrim(rtrim(number_format($available, 3, '.', ''), '0'), '.').'.']);
                }
                $line = CourierConsignmentLine::query()->firstOrCreate(
                    ['run_id' => $run->id, 'product_id' => $product->id],
                    ['quantity_assigned' => 0, 'quantity_sold' => 0, 'quantity_returned' => 0, 'unit_price' => (int) $product->price],
                );
                $line->increment('quantity_assigned', $quantity);
                $movement->lines()->createMany([
                    ['product_id' => $product->id, 'location_id' => $shop->id, 'quantity_delta' => -$quantity, 'unit_cost' => $product->price_market, 'unit' => 'piece', 'notes' => 'Consignation '.$run->reference],
                    ['product_id' => $product->id, 'location_id' => $courierLocation->id, 'quantity_delta' => $quantity, 'unit_cost' => $product->price_market, 'unit' => 'piece', 'notes' => 'Stock confié au livreur'],
                ]);
                $eventLines[] = ['product_id' => $product->id, 'name' => $product->name, 'quantity' => $quantity];
            }
            $this->event($run, 'assignment', $actor, $movement, 0, $eventLines);
            $this->audit($run, $actor, 'courier.consigned', $eventLines);
            return $run->fresh(['lines.product', 'courier', 'shop']);
        });
    }

    public function recordDeliveredOrder(CourierConsignmentRun $run, Order $order, StockMovement $movement, User $actor, int $cashCollected = 0): void
    {
        DB::transaction(function () use ($run, $order, $movement, $actor, $cashCollected): void {
            $locked = CourierConsignmentRun::query()->lockForUpdate()->findOrFail($run->id);
            if ($locked->status !== 'open') {
                throw ValidationException::withMessages(['delivery' => 'La tournée liée à cette commande est déjà clôturée.']);
            }

            $lines = $locked->lines()->lockForUpdate()->get()->keyBy('product_id');
            $deliveredLines = [];
            foreach ($order->orderItems->groupBy('product_id') as $productId => $items) {
                $line = $lines->get((int) $productId);
                $quantity = (int) $items->sum(fn ($item) => (float) $item->quantity);
                if (! $line || $quantity <= 0 || (float) $line->quantity_sold + $quantity + (float) $line->quantity_returned > (float) $line->quantity_assigned) {
                    $name = $items->first()?->product?->name ?? 'un produit';
                    throw ValidationException::withMessages(['delivery' => 'Stock de tournée insuffisant pour livrer '.$name.'. Demande un réassort au responsable boutique.']);
                }
                $line->increment('quantity_sold', $quantity);
                $deliveredLines[] = ['product_id' => (int) $productId, 'name' => $items->first()?->product?->name ?? 'Produit', 'quantity' => $quantity];
            }

            $deliveredLines[] = ['order_id' => $order->id, 'order_reference' => $order->code];
            $this->event($locked, 'sales', $actor, $movement, max(0, $cashCollected), $deliveredLines, 'Ventes calculées automatiquement depuis la commande livrée '.$order->code);
            $this->audit($locked, $actor, 'courier.delivery_recorded', ['order_id' => $order->id, 'order_reference' => $order->code, 'lines' => $deliveredLines, 'cash_collected' => max(0, $cashCollected)]);
        });
    }

    public function expectedCash(CourierConsignmentRun $run): int
    {
        $salesEvents = $run->events->where('event_type', 'sales');
        $orderIds = $salesEvents->flatMap(fn (CourierConsignmentEvent $event) => collect($event->lines ?? [])
            ->pluck('order_id')->filter())->unique()->values();
        $orders = $orderIds->isEmpty()
            ? collect()
            : Order::query()->with('payments')->whereIn('id', $orderIds)->get()->keyBy('id');

        $expected = 0;
        foreach ($salesEvents as $event) {
            $orderId = collect($event->lines ?? [])->pluck('order_id')->filter()->first();
            $order = $orderId ? $orders->get($orderId) : null;
            if (! $order) {
                $expected += (int) $event->amount;
                continue;
            }
            $prepaid = (int) $order->payments->where('status', 'collected')->whereNotIn('stage', ['on_delivery'])->sum('amount');
            $cashAtDelivery = (int) $order->payments->where('status', 'collected')->where('stage', 'on_delivery')->where('method', 'cash')->sum('amount');
            $merchandiseTotal = max(0, (int) $order->total);
            $expected += min($cashAtDelivery, max(0, $merchandiseTotal - min($merchandiseTotal, $prepaid)));
        }
        return $expected;
    }

    public function settle(CourierConsignmentRun $run, array $returnedLines, int $cashAmount, bool $close, User $actor, FinanceCashLedgerService $cashLedger): CourierConsignmentRun
    {
        return DB::transaction(function () use ($run, $returnedLines, $cashAmount, $close, $actor, $cashLedger): CourierConsignmentRun {
            $locked = CourierConsignmentRun::query()->lockForUpdate()->findOrFail($run->id);
            if ($locked->status !== 'open') {
                throw ValidationException::withMessages(['run' => 'Cette tournée est déjà clôturée.']);
            }
            $lines = $locked->lines()->with('product')->lockForUpdate()->get()->keyBy('product_id');
            $returned = $this->quantityMap($returnedLines);
            foreach ($returned as $productId => $quantity) {
                if (! isset($lines[$productId])) {
                    throw ValidationException::withMessages(['returned' => 'Produit inconnu dans cette tournée.']);
                }
            }
            foreach ($lines as $productId => $line) {
                $soldQty = (float) $line->quantity_sold;
                $returnedQty = $close ? ($returned[$productId] ?? 0.0) : (float) $line->quantity_returned;
                if ($soldQty + $returnedQty > (float) $line->quantity_assigned) {
                    throw ValidationException::withMessages(['lines' => 'Vendu + rendu dépasse la quantité confiée pour '.$line->product->name.'.']);
                }
                if ($close && abs($soldQty + $returnedQty - (float) $line->quantity_assigned) > 0.0001) {
                    throw ValidationException::withMessages(['returned' => 'Le décompte de '.$line->product->name.' ne correspond pas : confié '.rtrim(rtrim(number_format((float) $line->quantity_assigned, 3, '.', ''), '0'), '.').', vendu '.rtrim(rtrim(number_format($soldQty, 3, '.', ''), '0'), '.').', le reste doit être rendu.']);
                }
                if ($close) {
                    $returnDelta = $returnedQty - (float) $line->quantity_returned;
                    if ($returnDelta > 0) {
                        $line->quantity_returned = $returnedQty;
                    }
                }
                $line->save();
            }
            if ($close) {
                $returnLines = [];
                foreach ($lines as $line) {
                    // The just-submitted return quantity is all remaining assigned stock.
                    $qty = (float) $line->quantity_returned - (float) $line->quantity_sold;
                    if ($qty > 0) $returnLines[] = ['line' => $line, 'quantity' => $qty];
                }
                if ($returnLines) {
                    $movement = $this->movement($locked, 'courier_return', 'Retours de fin de tournée · '.$locked->reference, $actor);
                    $eventLines = [];
                    foreach ($returnLines as $item) {
                        $line = $item['line'];
                        $movement->lines()->createMany([
                            ['product_id' => $line->product_id, 'location_id' => $locked->courier_location_id, 'quantity_delta' => -$item['quantity'], 'unit_cost' => $line->product->price_market, 'unit' => 'piece', 'notes' => 'Retour du livreur'],
                            ['product_id' => $line->product_id, 'location_id' => $locked->shop_location_id, 'quantity_delta' => $item['quantity'], 'unit_cost' => $line->product->price_market, 'unit' => 'piece', 'notes' => 'Réintégré en boutique après tournée'],
                        ]);
                        $eventLines[] = ['product_id' => $line->product_id, 'name' => $line->product->name, 'quantity' => $item['quantity']];
                    }
                    $this->event($locked, 'returns', $actor, $movement, 0, $eventLines);
                }
            }
            $salesValue = (int) round((float) $lines->sum(fn ($line) => (float) $line->quantity_sold * (int) $line->unit_price));
            $locked->load('events');
            $cashExpected = $this->expectedCash($locked);
            $unremitted = max(0, $cashExpected - (int) $locked->cash_received);
            if ($cashAmount < 0 || $cashAmount > $unremitted) {
                throw ValidationException::withMessages(['cash_amount' => 'Le versement dépasse les espèces réellement encaissées encore à remettre ('.number_format($unremitted, 0, ',', ' ').' F CFA).']);
            }
            if ($close && $cashAmount !== $unremitted) {
                throw ValidationException::withMessages(['cash_amount' => 'Pour clôturer, remets le solde exact des espèces encaissées : '.number_format($unremitted, 0, ',', ' ').' F CFA.']);
            }
            if ($cashAmount > 0) {
                $cashMovement = $cashLedger->record([
                    'movement_type' => 'courier_sales_remittance', 'from_custody' => 'external', 'to_custody' => 'shop',
                    'amount' => $cashAmount, 'stock_location_id' => $locked->shop_location_id, 'source_type' => 'courier_consignment_run',
                    'source_id' => $locked->id, 'source_key' => 'courier-run-cash:'.$locked->id.':'.Str::uuid(),
                    'source_reference' => $locked->reference, 'occurred_at' => now(),
                    'description' => 'Versement des ventes de tournée '.$locked->reference.' · '.$locked->courier?->name,
                    'created_by' => $actor->id,
                ]);
                $locked->increment('cash_received', $cashAmount);
                $this->event($locked, 'cash_deposit', $actor, null, $cashAmount, [], null, $cashMovement);
            }
            if ($close) {
                $locked->update(['status' => 'closed', 'closed_by' => $actor->id, 'closed_at' => now()]);
                $this->event($locked, 'closed', $actor, null, $salesValue, []);
                $this->audit($locked, $actor, 'courier.consignment_closed', ['sales_value' => $salesValue, 'cash_received' => $locked->cash_received]);
            }
            return $locked->fresh(['lines.product', 'courier', 'shop', 'events.actor']);
        });
    }

    public function balance(int $locationId, int $productId): float
    {
        return (float) DB::table('stock_movement_lines as line')->join('stock_movements as movement', 'movement.id', '=', 'line.movement_id')
            ->where('movement.status', 'posted')->where('line.location_id', $locationId)->where('line.product_id', $productId)->sum('line.quantity_delta');
    }

    public function balances(int $locationId): array
    {
        return DB::table('stock_movement_lines as line')->join('stock_movements as movement', 'movement.id', '=', 'line.movement_id')
            ->where('movement.status', 'posted')->where('line.location_id', $locationId)
            ->select('line.product_id')->selectRaw('SUM(line.quantity_delta) as quantity')->groupBy('line.product_id')
            ->pluck('quantity', 'product_id')->map(fn ($quantity) => (float) $quantity)->all();
    }

    private function movement(CourierConsignmentRun $run, string $type, string $reason, User $actor): StockMovement
    {
        $movement = StockMovement::query()->create([
            'movement_number' => 'TMP-'.Str::uuid(), 'type' => $type, 'status' => 'posted',
            'source_type' => 'courier_consignment', 'source_reference' => $run->reference, 'occurred_at' => now(),
            'origin' => 'screen', 'reason' => $reason, 'created_by' => $actor->id, 'approved_by' => $actor->id,
            'posted_by' => $actor->id, 'approved_at' => now(), 'posted_at' => now(),
        ]);
        $movement->update(['movement_number' => sprintf('ST-LIV-%s-%06d', now()->format('Y'), $movement->id)]);
        return $movement;
    }

    private function event(CourierConsignmentRun $run, string $type, User $actor, ?StockMovement $movement, int $amount, array $lines, ?string $notes = null, $cashMovement = null): void
    {
        CourierConsignmentEvent::query()->create([
            'run_id' => $run->id, 'event_type' => $type, 'user_id' => $actor->id,
            'stock_movement_id' => $movement?->id, 'cash_movement_id' => $cashMovement?->id,
            'amount' => $amount, 'lines' => $lines ?: null, 'notes' => $notes, 'occurred_at' => now(),
        ]);
    }

    private function audit(CourierConsignmentRun $run, User $actor, string $action, array $values): void
    {
        StockAuditLog::query()->create([
            'user_id' => $actor->id, 'actor_role' => $actor->user_type, 'action' => $action,
            'subject_type' => CourierConsignmentRun::class, 'subject_id' => $run->id,
            'after_values' => $values, 'origin' => 'screen', 'result' => 'success',
        ]);
    }

    private function quantityMap(array $lines): array
    {
        $result = [];
        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            if ($productId > 0) $result[$productId] = (float) ($line['quantity'] ?? 0);
        }
        return $result;
    }
}
