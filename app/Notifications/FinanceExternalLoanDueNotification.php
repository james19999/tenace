<?php

namespace App\Notifications;

use App\Models\FinanceExternalLoan;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FinanceExternalLoanDueNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly FinanceExternalLoan $loan,
        private readonly string $dueOn,
        private readonly int $daysUntilDue = 0,
        private readonly int $daysLate = 0,
        private readonly ?string $reminderKey = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $dueDate = Carbon::parse($this->dueOn)->format('d/m/Y');
        $title = match (true) {
            $this->daysUntilDue > 1 => 'Échéance de prêt dans deux jours',
            $this->daysUntilDue === 1 => 'Échéance de prêt demain',
            $this->daysUntilDue === 0 => 'Échéance de prêt aujourd’hui',
            default => 'Échéance de prêt dépassée',
        };
        $status = match (true) {
            $this->daysUntilDue > 1 => 'dans '.$this->daysUntilDue.' jours',
            $this->daysUntilDue === 1 => 'demain',
            $this->daysUntilDue === 0 => 'aujourd’hui',
            default => 'en retard de '.$this->daysLate.' jour(s)',
        };

        return [
            'module' => 'finance',
            'title' => $title,
            'message' => $this->loan->lender_name.' · '.$this->loan->reference.' : échéance du '.$dueDate.' ('.$status.'). Reste à rembourser '.number_format($this->loan->remaining_amount, 0, ',', ' ').' F.',
            'url' => route('finance.loans.show', $this->loan),
            'icon' => 'event',
            'loan_id' => $this->loan->id,
            'due_on' => $this->dueOn,
            'days_until_due' => $this->daysUntilDue,
            'days_late' => $this->daysLate,
            'reminder_key' => $this->reminderKey,
        ];
    }
}
