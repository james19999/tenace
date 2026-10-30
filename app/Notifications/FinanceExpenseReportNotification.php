<?php

namespace App\Notifications;

use App\Models\FinanceExpenseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FinanceExpenseReportNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FinanceExpenseRequest $expense, private readonly string $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'module' => 'finance',
            'type' => 'finance_expense',
            'title' => 'Dépense à suivre · '.$this->expense->reference,
            'message' => $this->event,
            'amount' => (int) $this->expense->amount,
            'url' => route('finance.expenses.show', $this->expense),
            'expense_id' => $this->expense->id,
            'icon' => 'request_quote',
        ];
    }
}
