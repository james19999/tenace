<?php

namespace App\Notifications;

use App\Models\FinanceCashClosure;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FinanceCashCountAlertNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FinanceCashClosure $closure)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'finance_cash_count_discrepancy',
            'title' => 'Écart au comptage de la caisse générale',
            'message' => 'Un écart de '.number_format(abs((int) $this->closure->variance_amount), 0, ',', ' ')
                .' F a été déclaré pour le '.
                $this->closure->closing_date->format('d/m/Y').'. Le contrôle du DG ou du responsable financier est attendu.',
            'closure_id' => $this->closure->id,
            'url' => route('finance.dashboard'),
        ];
    }
}
