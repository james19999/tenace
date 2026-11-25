<?php

namespace App\Notifications;

use App\Models\CourierOrderCancellationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourierCancellationDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly CourierOrderCancellationRequest $cancellationRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $approved = $this->cancellationRequest->status === 'approved';
        $isWaitingRequest = $this->cancellationRequest->request_type === 'waiting';
        $order = $this->cancellationRequest->order;
        $message = ($order?->code ?: 'La commande #'.$this->cancellationRequest->order_id)
            .($isWaitingRequest
                ? ($approved
                    ? ' est mise en attente par le responsable. Tu peux maintenant prendre une autre commande.'
                    : ' reste en cours. La demande de mise en attente a été refusée; termine-la avant d’en prendre une autre.')
                : ($approved
                    ? ' a été annulée. Tu peux prendre une autre commande.'
                    : ' reste à ta charge. Termine-la avant d’en prendre une autre.'));
        if (! $approved && filled($this->cancellationRequest->review_note)) {
            $message .= ' Motif du refus : '.$this->cancellationRequest->review_note;
        }

        return [
            'module' => 'courier_cancellation',
            'request_type' => $isWaitingRequest ? 'waiting' : 'cancellation',
            'title' => $isWaitingRequest
                ? ($approved ? 'Mise en attente acceptée' : 'Mise en attente refusée')
                : ($approved ? 'Annulation acceptée' : 'Annulation refusée'),
            'message' => $message,
            'url' => $isWaitingRequest
                ? route('authlivrable', ['id' => $this->cancellationRequest->courier_id, 'filter' => 'waiting'])
                : route('courier.orders.cancelled'),
            'icon' => $approved ? 'cancel' : 'delivery_dining',
        ];
    }
}
