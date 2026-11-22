<?php

namespace App\Notifications;

use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CanceledOrderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'module' => 'canceled_order',
            'title' => 'Commande annulée',
            'message' => 'La commande '.($this->order->code ?: '#'.$this->order->id).' a été annulée. Motif : '.(filled($this->order->motif) ? $this->order->motif : 'non renseigné').'.',
            'url' => route('order-cancel-list'),
            'icon' => 'cancel',
            'order_id' => (int) $this->order->id,
            'order_code' => (string) $this->order->code,
        ];
    }
}
