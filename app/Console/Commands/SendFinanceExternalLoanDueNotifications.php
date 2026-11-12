<?php

namespace App\Console\Commands;

use App\Services\FinanceExternalLoanDueNotificationService;
use Illuminate\Console\Command;

class SendFinanceExternalLoanDueNotifications extends Command
{
    protected $signature = 'finance:notify-loan-due';
    protected $description = 'Notifie les administrateurs des échéances de prêts arrivées';

    public function handle(FinanceExternalLoanDueNotificationService $notifications): int
    {
        $sent = $notifications->notifyDueLoans();
        $this->info($sent.' notification(s) d’échéance envoyée(s).');

        return self::SUCCESS;
    }
}
