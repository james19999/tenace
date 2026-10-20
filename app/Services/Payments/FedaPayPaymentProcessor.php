<?php

namespace App\Services\Payments;

use App\Models\OrderPayment;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FedaPayPaymentProcessor
{
    public function refresh(OrderPayment $payment, array $transaction): OrderPayment
    {
        $transactionId = (string) ($transaction['id'] ?? '');
        if ($transactionId === '' || $transactionId !== (string) $payment->gateway_transaction_id) {
            throw new RuntimeException('FedaPay transaction identifier does not match.');
        }
        if ((int) ($transaction['amount'] ?? -1) !== (int) $payment->amount) {
            throw new RuntimeException('FedaPay transaction amount does not match.');
        }
        $currency = strtoupper((string) (data_get($transaction, 'currency.iso')
            ?? $transaction['currency_iso']
            ?? ''));
        if ($currency !== 'XOF') {
            throw new RuntimeException('FedaPay transaction currency is not XOF.');
        }

        $status = strtolower((string) ($transaction['status'] ?? ''));
        $reference = isset($transaction['reference']) ? (string) $transaction['reference'] : null;

        return DB::transaction(function () use ($payment, $status, $reference): OrderPayment {
            $lockedPayment = OrderPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $lockedOrder = Order::query()->whereKey($lockedPayment->order_id)->lockForUpdate()->firstOrFail();

            $lockedPayment->gateway_status = $status;
            if ($reference) {
                $lockedPayment->gateway_reference = $reference;
                $lockedPayment->transaction_reference = $reference;
            }

            if ($status === 'approved' && $lockedPayment->status !== 'confirmed') {
                $balance = max(0, (int) $lockedOrder->montant - (int) $lockedOrder->paid_amount);
                if ((int) $lockedPayment->amount > $balance) {
                    Log::warning('FedaPay approved transaction exceeds the remaining order balance.', [
                        'payment_id' => $lockedPayment->id,
                        'order_id' => $lockedOrder->id,
                        'gateway_transaction_id' => $lockedPayment->gateway_transaction_id,
                    ]);
                    $lockedPayment->save();

                    return $lockedPayment;
                }

                $lockedPayment->status = 'confirmed';
                $lockedPayment->confirmed_at = now();
                $lockedOrder->paid_amount = (int) $lockedOrder->paid_amount + (int) $lockedPayment->amount;
                $lockedOrder->save();
            } elseif (in_array($status, ['declined', 'canceled'], true) && $lockedPayment->status !== 'confirmed') {
                $lockedPayment->status = 'failed';
            } elseif (str_contains($status, 'refunded')) {
                if ($lockedPayment->status === 'confirmed') {
                    $lockedPayment->status = 'refunded';
                    if ($lockedOrder->partner_order_status !== 'delivered') {
                        $lockedOrder->paid_amount = max(0, (int) $lockedOrder->paid_amount - (int) $lockedPayment->amount);
                        $lockedOrder->save();
                    }
                }
                Log::warning('FedaPay reports a refund for a delivery payment; manual reconciliation is required.', [
                    'payment_id' => $lockedPayment->id,
                    'order_id' => $lockedOrder->id,
                    'gateway_transaction_id' => $lockedPayment->gateway_transaction_id,
                ]);
            }

            $lockedPayment->save();

            return $lockedPayment;
        });
    }
}
