<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockControlNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $event,
        public string $title,
        public string $message,
        public string $url,
        public string $icon = 'inventory_2',
        public string $tab = 'alerts',
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'module' => 'stock_control',
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icon,
            'tab' => $this->tab,
        ];
    }
}
