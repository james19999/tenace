<?php

namespace App\Listeners;

use App\Services\FinanceExternalLoanDueNotificationService;
use Illuminate\Auth\Events\Login;

class NotifyAdminExternalLoanDueOnLogin
{
    public function handle(Login $event): void
    {
        app(FinanceExternalLoanDueNotificationService::class)->notifyDueLoans($event->user);
    }
}
