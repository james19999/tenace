<?php

namespace App\Http\Livewire;

use App\Notifications\CustomerServiceCaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CustomerServiceNotifications extends Component
{
    public bool $open = false;
    public bool $loginNotificationShown = false;
    public array $knownNotificationIds = [];

    public function mount(): void
    {
        $this->knownNotificationIds = array_map('strval', session()->get($this->seenNotificationsSessionKey(), []));
    }

    public function showUnreadOnLogin(): void
    {
        if ($this->loginNotificationShown) {
            return;
        }

        $this->loginNotificationShown = true;
        $recentIds = Auth::user()->notifications()
            ->where('type', CustomerServiceCaseNotification::class)
            ->latest()
            ->limit(30)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $unread = Auth::user()->unreadNotifications()
            ->where('type', CustomerServiceCaseNotification::class);
        $unreadIds = (clone $unread)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $knownIds = array_map('strval', $this->knownNotificationIds);
        $notification = (clone $unread)
            ->whereNotIn('id', $knownIds ?: ['__none__'])
            ->latest()
            ->first();

        $this->rememberNotificationIds(array_merge($recentIds, $unreadIds));

        if ($notification) {
            $this->dispatchBrowserEvent('customer-service-notifications-push', [
                'notification' => $this->notificationData($notification),
                'unreadCount' => (clone $unread)->count(),
                'playSound' => true,
            ]);
        }
    }

    public function checkForNewNotifications(): void
    {
        $latest = Auth::user()->notifications()
            ->where('type', CustomerServiceCaseNotification::class)
            ->latest()
            ->limit(30)
            ->get();
        $knownIds = array_map('strval', $this->knownNotificationIds);
        $newUnread = $latest->filter(fn ($notification) =>
            ! in_array((string) $notification->id, $knownIds, true) && is_null($notification->read_at)
        );

        $this->rememberNotificationIds($latest->pluck('id')->map(fn ($id) => (string) $id)->all());

        if ($newUnread->isNotEmpty()) {
            $this->dispatchBrowserEvent('customer-service-notifications-push', [
                'notification' => $this->notificationData($newUnread->first()),
                'unreadCount' => Auth::user()->unreadNotifications()
                    ->where('type', CustomerServiceCaseNotification::class)
                    ->count(),
                'playSound' => true,
            ]);
        }
    }

    protected function notificationData($notification): array
    {
        return [
            'id' => (string) $notification->id,
            'title' => $notification->data['case_number'] ?? 'Service client',
            'message' => $notification->data['message'] ?? 'Vous avez une nouvelle notification.',
            'url' => $notification->data['url'] ?? route('service-cases.index'),
        ];
    }

    protected function seenNotificationsSessionKey(): string
    {
        return 'customer_service_notification_push_seen_'.Auth::id();
    }

    protected function rememberNotificationIds(array $ids): void
    {
        $this->knownNotificationIds = array_slice(
            array_values(array_unique(array_merge(array_map('strval', $this->knownNotificationIds), array_map('strval', $ids)))),
            -100
        );
        session()->put($this->seenNotificationsSessionKey(), $this->knownNotificationIds);
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markAsRead(string $notificationId): void
    {
        Auth::user()->unreadNotifications()
            ->where('type', CustomerServiceCaseNotification::class)
            ->whereKey($notificationId)
            ->firstOrFail()
            ->markAsRead();
    }

    public function openNotification(string $notificationId): void
    {
        $notification = Auth::user()->unreadNotifications()
            ->where('type', CustomerServiceCaseNotification::class)
            ->whereKey($notificationId)
            ->firstOrFail();

        $url = $notification->data['url'] ?? null;
        $caseId = $notification->data['case_id'] ?? null;
        $notification->markAsRead();

        $this->redirect($url ?: ($caseId
            ? route('service-cases.show', $caseId)
            : route('service-cases.index')));
    }

    public function render()
    {
        $unread = Auth::user()->unreadNotifications()
            ->where('type', CustomerServiceCaseNotification::class);

        return view('livewire.customer-service-notifications', [
            'notificationCount' => (clone $unread)->count(),
            'notifications' => (clone $unread)->latest()->limit(6)->get(),
        ]);
    }
}
