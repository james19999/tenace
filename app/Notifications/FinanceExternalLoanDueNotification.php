<?php

namespace App\Notifications;

use App\Models\FinanceExternalLoan;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FinanceExternalLoanDueNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FinanceExternalLoan $loan, private readonly string $dueOn)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $overdue = Carbon::parse($this->dueOn)->lt(today());

        return [
            'module' => 'finance',
            'title' => $overdue ? 'Échéance de prêt dépassée' : 'Échéance de prêt aujourd’hui',
            'message' => $this->loan->lender_name.' · '.$this->loan->reference.' : échéance du '.Carbon::parse($this->dueOn)->format('d/m/Y').'. Reste à rembourser '.number_format($this->loan->remaining_amount, 0, ',', ' ').' F.',
            'url' => route('finance.loans.show', $this->loan),
            'icon' => 'event',
            'loan_id' => $this->loan->id,
            'due_on' => $this->dueOn,
        ];
    }
}
