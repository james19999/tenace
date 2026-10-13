<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\StockControlNotification;

class StockControlSidebarBadgeService
{
    public function counts(User $user): array
    {
        $tabs = ['settings', 'balances', 'receipts', 'transfers', 'counts', 'adjustments', 'movements', 'alerts'];
        $counts = array_fill_keys($tabs, 0);

        $user->unreadNotifications()
            ->where('type', StockControlNotification::class)
            ->get(['data'])
            ->each(function ($notification) use (&$counts, $tabs): void {
                $tab = $notification->data['tab'] ?? $this->legacyTab($notification->data);
                if (in_array($tab, $tabs, true)) {
                    $counts[$tab]++;
                }
            });

        return $counts;
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
}
