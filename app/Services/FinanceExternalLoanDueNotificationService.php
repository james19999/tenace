<?php

namespace App\Services;

use App\Models\FinanceExternalLoan;
use App\Models\User;
use App\Notifications\FinanceExternalLoanDueNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class FinanceExternalLoanDueNotificationService
{
    public function notifyDueLoans(?User $admin = null, bool $onLogin = false): int
    {
        if (! Schema::hasTable('finance_external_loans') || ! Schema::hasTable('notifications')) {
            return 0;
        }

        $admins = $admin
            ? collect([$admin])->filter(fn (User $user) => $user->isAdmin() && $user->active)
            : User::query()->where('user_type', User::ROLE_ADMIN)->where('active', true)->get();
        if ($admins->isEmpty()) {
            return 0;
        }

        $sent = 0;
        FinanceExternalLoan::query()
            ->where('status', 'active')
            ->whereNotNull('next_payment_on')
            ->whereDate('next_payment_on', '<=', today()->addDays(2))
            ->orderBy('next_payment_on')
            ->chunkById(100, function ($loans) use ($admins, $onLogin, &$sent): void {
                foreach ($loans as $loan) {
                    $dueOn = $loan->next_payment_on->toDateString();
                    $daysUntilDue = (int) today()->diffInDays(Carbon::parse($dueOn)->startOfDay(), false);
                    $daysLate = max(0, -$daysUntilDue);
                    $reminderPeriod = match (true) {
                        $daysUntilDue > 0 => 'before_'.$daysUntilDue,
                        $daysUntilDue === 0 => 'due',
                        default => 'overdue_'.intdiv($daysLate, 2),
                    };

                    foreach ($admins as $recipient) {
                        if (! $onLogin) {
                            $alreadyNotified = $recipient->notifications()
                                ->where('type', FinanceExternalLoanDueNotification::class)
                                ->where('data->loan_id', $loan->id)
                                ->where('data->due_on', $dueOn)
                                ->where('data->reminder_key', $reminderPeriod)
                                ->exists();

                            if ($alreadyNotified) {
                                continue;
                            }
                        }

                        $recipient->notify(new FinanceExternalLoanDueNotification(
                            $loan,
                            $dueOn,
                            $daysUntilDue,
                            $daysLate,
                            $onLogin ? null : $reminderPeriod,
                        ));
                        $sent++;
                    }
                }
            });

        return $sent;
    }
}
