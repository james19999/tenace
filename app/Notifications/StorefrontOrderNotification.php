<?php

namespace App\Notifications;

use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StorefrontOrderNotification extends Notification
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
            'module' => 'storefront_order',
            'title' => 'Nouvelle commande du site',
            'message' => 'Nouvelle commande '.$this->order->code.' reçue sur le site. Ouvrez-la pour la traiter.',
            'url' => route('ordershow', $this->order->id),
            'icon' => 'shopping_bag',
            'order_id' => (int) $this->order->id,
            'order_code' => (string) $this->order->code,
        ];
    }
}
