<?php

namespace App\Services;

use App\Models\FinanceExternalLoan;
use App\Models\User;
use App\Notifications\FinanceExternalLoanDueNotification;
use Illuminate\Support\Facades\Schema;

class FinanceExternalLoanDueNotificationService
{
    public function notifyDueLoans(?User $admin = null): int
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
            ->whereDate('next_payment_on', '<=', today())
            ->orderBy('next_payment_on')
            ->chunkById(100, function ($loans) use ($admins, &$sent): void {
                foreach ($loans as $loan) {
                    $dueOn = $loan->next_payment_on->toDateString();
                    foreach ($admins as $recipient) {
                        $alreadyNotified = $recipient->notifications()
                            ->where('type', FinanceExternalLoanDueNotification::class)
                            ->where('data->loan_id', $loan->id)
                            ->where('data->due_on', $dueOn)
                            ->exists();

                        if ($alreadyNotified) {
                            continue;
                        }

                        $recipient->notify(new FinanceExternalLoanDueNotification($loan, $dueOn));
                        $sent++;
                    }
                }
            });

        return $sent;
    }
}
