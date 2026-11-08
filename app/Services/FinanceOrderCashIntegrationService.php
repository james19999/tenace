<?php

namespace App\Services;

use App\Models\FinanceCashMovement;
use App\Models\OrderPayment;
use App\Models\Orders\Order;
use App\Models\StockMovement;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FinanceOrderCashIntegrationService
{
    public function __construct(private readonly FinanceCashLedgerService $ledger)
    {
    }

    public function syncPayment(OrderPayment $payment, ?int $locationId = null): ?FinanceCashMovement
    {
        if (! in_array($payment->method, ['cash', 'manual'], true)
            || $payment->status !== 'collected'
            || (int) $payment->amount <= 0) {
            return null;
        }

        $order = Order::query()->find($payment->order_id);
        if (! $order
            || $order->partner_id
            || $order->public_order_link_id
            || $payment->stage === 'on_delivery') {
            return null;
        }

        if (! $locationId) {
            $rules = app(StockModuleSettingsService::class)->rules();
            if ($order->is_wholesale && ! empty($rules['wholesale_sales_location_id'])) {
                $locationId = (int) $rules['wholesale_sales_location_id'];
            }
        }
        if (! $locationId && ! $order->is_wholesale) {
            $managerId = $order->created_user ?: $order->order_by;
            if ($managerId) {
                $locationId = StockLocation::query()->where('type', 'shop')->where('is_active', true)
                    ->where('responsible_user_id', $managerId)->value('id');
            }
            if (! $locationId) {
                $locationId = app(ShopSalesLocationResolver::class)->forOrder($order, false)?->id;
            }
            if (! $locationId) {
                $rules = app(StockModuleSettingsService::class)->rules();
                $locationId = $rules['default_sales_location_id'] ?? null;
            }
        }
        if (! $locationId) {
            $locationId = StockMovement::query()
                ->where('order_id', $order->id)
                ->where('type', 'sale')
                ->where('status', 'posted')
                ->with('lines.location')
                ->get()
                ->flatMap(fn (StockMovement $movement) => $movement->lines)
                ->first(fn ($line) => $line->location?->type === 'shop')
                ?->location_id;
        }
        $location = $locationId ? StockLocation::query()->whereKey($locationId)->where('type', 'shop')->where('is_active', true)->first() : null;
        if (! $location) {
            return null;
        }
        $locationId = (int) $location->id;

        $sourceKey = 'order-payment:'.$payment->id;
        $existingMovement = FinanceCashMovement::query()->where('source_key', $sourceKey)->first();
        if ($existingMovement) {
            return $existingMovement;
        }

        // The order total contains merchandise only; `montant` and collected
        // payments may also contain delivery fees. Cap shop cash at the
        // merchandise total and allocate partial payments in collection order.
        $merchandiseTotal = max(0, (int) $order->total);
        $cashPaymentIds = OrderPayment::query()->where('order_id', $order->id)
            ->where('status', 'collected')->whereIn('method', ['cash', 'manual'])->pluck('id');
        $merchandiseAlreadyRecorded = (int) FinanceCashMovement::query()
            ->where('movement_type', 'sale_cash_received')
            ->where('source_type', 'order_payment')->whereIn('source_id', $cashPaymentIds)->sum('amount');
        $amountForShop = min((int) $payment->amount, max(0, $merchandiseTotal - $merchandiseAlreadyRecorded));
        if ($amountForShop <= 0) {
            return null;
        }

        return $this->ledger->record([
            'movement_type' => 'sale_cash_received',
            'from_custody' => 'external',
            'to_custody' => 'shop',
            'amount' => $amountForShop,
            'stock_location_id' => $locationId,
            'source_type' => 'order_payment',
            'source_id' => $payment->id,
            'source_key' => $sourceKey,
            'source_reference' => $order->code ?: (string) $order->id,
            'occurred_at' => $payment->collected_at ?? $payment->created_at ?? now(),
            'description' => 'Encaissement espèces lié à la commande '.$order->code.'.',
            'metadata' => ['order_id' => $order->id, 'payment_method' => $payment->method, 'stage' => $payment->stage],
            'created_by' => $payment->collected_by,
        ]);
    }

    public function syncOrder(Order $order, ?int $locationId = null, ?int $validatedBy = null): void
    {
        DB::transaction(function () use ($order, $locationId, $validatedBy): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== 'delivered' || $lockedOrder->partner_id || $lockedOrder->public_order_link_id) {
                return;
            }

            $lockedOrder->payments()->where('status', 'collected')->whereIn('method', ['cash', 'manual'])
                ->get()->each(fn (OrderPayment $payment) => $this->syncPayment($payment, $locationId));

            // For internal shop sales, final delivery validation confirms cash
            // collection. Record any remaining balance once so the cash ledger
            // reflects the existing checkout workflow without a second entry.
            $amountDue = max(0, (int) ($lockedOrder->montant ?? ((int) $lockedOrder->total + (int) $lockedOrder->tax)));
            $recordedPayments = (int) $lockedOrder->payments()
                ->whereIn('status', ['collected', 'confirmed'])->sum('amount');
            $alreadyPaid = min($amountDue, max((int) $lockedOrder->paid_amount, $recordedPayments));
            $remaining = $amountDue - $alreadyPaid;

            if ($remaining > 0) {
                $lockedOrder->payments()->create([
                    'stage' => 'order_validation',
                    'method' => 'cash',
                    'amount' => $remaining,
                    'status' => 'collected',
                    'collected_by' => $validatedBy ?: $lockedOrder->created_user ?: $lockedOrder->order_by,
                    'collected_at' => $lockedOrder->delivered_at ?? now(),
                    'notes' => 'Encaissement espèces enregistré automatiquement à la validation finale de la commande.',
                ]);
            }

            if ((int) $lockedOrder->paid_amount < $alreadyPaid + $remaining) {
                $lockedOrder->paid_amount = $alreadyPaid + $remaining;
                $lockedOrder->save();
            }
        });
    }

    /** Reverse shop-cash entries when an order is canceled and its collected cash is returned. */
    public function refundCashForCanceledOrder(Order $order, User $actor): int
    {
        if ($order->partner_id || $order->public_order_link_id) {
            return 0;
        }

        return DB::transaction(function () use ($order, $actor): int {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $refunded = 0;
            $payments = OrderPayment::query()->where('order_id', $lockedOrder->id)
                ->where('status', 'collected')->whereIn('method', ['cash', 'manual'])->lockForUpdate()->get();

            foreach ($payments as $payment) {
                $salesMovements = FinanceCashMovement::query()->where('movement_type', 'sale_cash_received')
                    ->where('source_type', 'order_payment')->where('source_id', $payment->id)->get();
                $paymentRefunded = false;

                foreach ($salesMovements as $saleMovement) {
                    $amount = (int) $saleMovement->amount;
                    if ($amount <= 0) {
                        continue;
                    }

                    $this->ledger->record([
                        'movement_type' => 'cash_refund',
                        'from_custody' => 'shop',
                        'to_custody' => 'external',
                        'amount' => $amount,
                        'stock_location_id' => $saleMovement->stock_location_id,
                        'source_type' => 'order_cancellation',
                        'source_id' => $lockedOrder->id,
                        'source_key' => 'order-cancel-refund:'.$saleMovement->id,
                        'source_reference' => $lockedOrder->code ?: (string) $lockedOrder->id,
                        'occurred_at' => now(),
                        'description' => 'Remboursement des espèces encaissées après annulation de la commande '.$lockedOrder->code.'.',
                        'metadata' => ['order_id' => $lockedOrder->id, 'order_payment_id' => $payment->id, 'sale_movement_id' => $saleMovement->id],
                        'created_by' => $actor->id,
                    ]);
                    $refunded += $amount;
                    $paymentRefunded = true;
                }

                if ($paymentRefunded) {
                    $payment->update(['status' => 'refunded']);
                }
            }

            return $refunded;
        });
    }
}
