<?php

namespace App\Observers;

use App\Models\OrderPayment;
use App\Services\FinanceOrderCashIntegrationService;

class OrderPaymentObserver
{
    public function created(OrderPayment $payment): void
    {
        app(FinanceOrderCashIntegrationService::class)->syncPayment($payment);
    }

    public function updated(OrderPayment $payment): void
    {
        if ($payment->wasChanged('status') && $payment->status === 'collected') {
            app(FinanceOrderCashIntegrationService::class)->syncPayment($payment);
        }
    }
}
