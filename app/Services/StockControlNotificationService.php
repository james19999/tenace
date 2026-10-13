<?php

namespace App\Services;

use App\Models\StockAlert;
use App\Models\User;
use App\Notifications\StockControlNotification;

class StockControlNotificationService
{
    public function markTabAsRead(User $user, string $tab): void
    {
        $ids = $user->unreadNotifications()
            ->where('type', StockControlNotification::class)
            ->get()
            ->filter(fn ($notification) => ($notification->data['tab'] ?? $this->legacyTab($notification->data)) === $tab)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            $user->unreadNotifications()->whereIn('id', $ids)->update(['read_at' => now()]);
        }
    }

    private function legacyTab(array $data): ?string
    {
        $event = $data['event'] ?? '';

        return match (true) {
            str_starts_with($event, 'adjustment_') => 'adjustments',
            str_starts_with($event, 'count_') => 'counts',
            str_starts_with($event, 'receipt_') => 'receipts',
            str_starts_with($event, 'transfer_') => 'transfers',
            str_starts_with($event, 'stock_alert_') => 'alerts',
            default => null,
        };
    }

    public function toRoles(array $roles, StockControlNotification $notification, ?int $exceptUserId = null): void
    {
        User::query()
            ->where('active', true)
            ->whereIn('user_type', $roles)
            ->when($exceptUserId, fn ($query) => $query->where('id', '<>', $exceptUserId))
            ->get()
            ->each(fn (User $user) => $user->notify(clone $notification));
    }

    public function toUsers(array $userIds, StockControlNotification $notification, ?int $exceptUserId = null): void
    {
        User::query()
            ->where('active', true)
            ->whereIn('id', array_values(array_unique(array_filter($userIds))))
            ->when($exceptUserId, fn ($query) => $query->where('id', '<>', $exceptUserId))
            ->get()
            ->each(fn (User $user) => $user->notify(clone $notification));
    }

    public function notifyAlert(StockAlert $alert): void
    {
        $notification = new StockControlNotification(
            'stock_alert_created',
            $alert->title,
            $alert->message,
            route('stock-control.alerts.index'),
            $alert->severity === 'critical' ? 'error' : 'warning',
            'alerts',
        );

        if ($alert->assigned_to) {
            $this->toUsers([(int) $alert->assigned_to], $notification);
        } else {
            $this->toRoles(['ADMINUSER', 'MNG'], $notification);
        }
    }
}
