<?php

namespace App\Notifications;

use App\Models\CourierOrderCancellationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourierCancellationRequestNotification extends Notification
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
        $order = $this->cancellationRequest->order;
        $isWaitingRequest = $this->cancellationRequest->request_type === 'waiting';

        return [
            'module' => 'courier_cancellation',
            'title' => $isWaitingRequest ? 'Mise en attente à valider' : 'Annulation à valider',
            'message' => ($this->cancellationRequest->courier?->name ?? 'Un livreur')
                .($isWaitingRequest ? ' demande la mise en attente de ' : ' demande l’annulation de ')
                .($order?->code ?: 'la commande #'.$this->cancellationRequest->order_id)
                .' : '.$this->cancellationRequest->reason,
            'url' => route('order-cancel-list'),
            'icon' => 'pending_actions',
            'order_id' => (int) $this->cancellationRequest->order_id,
            'cancellation_request_id' => (int) $this->cancellationRequest->id,
            'request_type' => $this->cancellationRequest->request_type ?: 'cancellation',
        ];
    }
}
