<?php

namespace App\Http\Livewire;

use App\Notifications\CustomerServiceCaseNotification;
use App\Notifications\StockControlNotification;
use App\Notifications\DgAppointmentNotification;
use App\Notifications\FinanceCashCountAlertNotification;
use App\Notifications\FinanceExpenseReportNotification;
use App\Notifications\FinanceExternalLoanDueNotification;
use App\Notifications\FinanceWorkflowNotification;
use App\Models\Orders\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CustomerServiceNotifications extends Component
{
    public bool $open = false;
    public bool $loginNotificationShown = false;
    public array $knownNotificationIds = [];
    public array $knownScheduledCourierOrderIds = [];

    public function mount(): void
    {
        $this->knownNotificationIds = array_map('strval', session()->get($this->seenNotificationsSessionKey(), []));
        $this->knownScheduledCourierOrderIds = $this->scheduledCourierOrders()->modelKeys();
    }

    public function showUnreadOnLogin(): void
    {
        if ($this->loginNotificationShown) {
            return;
        }

        $this->loginNotificationShown = true;
        $recentIds = Auth::user()->notifications()
            ->whereIn('type', $this->notificationTypes())
            ->latest()
            ->limit(30)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $unread = Auth::user()->unreadNotifications()
            ->whereIn('type', $this->notificationTypes());
        $unreadIds = (clone $unread)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $knownIds = array_map('strval', $this->knownNotificationIds);
        $notification = (clone $unread)
            ->whereNotIn('id', $knownIds ?: ['__none__'])
            ->latest()
            ->first();

        $this->rememberNotificationIds(array_merge($recentIds, $unreadIds));

        // This component is mounted on every page. Only offer the initial unread
        // notification once per authenticated session; polling handles arrivals
        // after that without replaying the same unread item on every navigation.
        $initialPushKey = $this->initialPushSessionKey();
        if (session()->get($initialPushKey, false)) {
            return;
        }
        session()->put($initialPushKey, true);

        if ($notification) {
            $this->dispatchBrowserEvent('customer-service-notifications-push', [
                'notification' => $this->notificationData($notification),
                'unreadCount' => (clone $unread)->count(),
                'playSound' => true,
            ]);
        } elseif (($courierOrders = $this->scheduledCourierOrders())->isNotEmpty()) {
            $courseCount = $courierOrders->count();
            $this->dispatchBrowserEvent('customer-service-notifications-push', [
                'notification' => [
                    'title' => 'Courses programmées disponibles',
                    'message' => $courseCount === 1
                        ? 'Une course programmée attend un livreur. Ouvre les notifications pour la consulter.'
                        : $courseCount.' courses programmées attendent un livreur. Ouvre les notifications pour les consulter.',
                    'url' => route('livrable'),
                    'icon' => 'local_shipping',
                ],
                'unreadCount' => (clone $unread)->count() + $courierOrders->count(),
                'playSound' => true,
            ]);
        }
    }

    public function checkForNewNotifications(): void
    {
        $latest = Auth::user()->notifications()
            ->whereIn('type', $this->notificationTypes())
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
                    ->whereIn('type', $this->notificationTypes())
                    ->count(),
                'playSound' => true,
            ]);
        }

        $courierOrders = $this->scheduledCourierOrders();
        $scheduledOrderIds = array_map('strval', $courierOrders->modelKeys());
        $newScheduledOrderIds = array_values(array_diff($scheduledOrderIds, array_map('strval', $this->knownScheduledCourierOrderIds)));
        $this->knownScheduledCourierOrderIds = $scheduledOrderIds;

        if ($newScheduledOrderIds !== []) {
            $newCourseCount = count($newScheduledOrderIds);
            $this->dispatchBrowserEvent('customer-service-notifications-push', [
                'notification' => [
                    'title' => 'Nouvelle course programmée',
                    'message' => $newCourseCount === 1
                        ? 'Une nouvelle course est disponible pour les livreurs.'
                        : $newCourseCount.' nouvelles courses sont disponibles pour les livreurs.',
                    'url' => route('livrable'),
                    'icon' => 'local_shipping',
                ],
                'unreadCount' => Auth::user()->unreadNotifications()
                    ->whereIn('type', $this->notificationTypes())
                    ->count() + $courierOrders->count(),
                'playSound' => true,
            ]);
        }
    }

    protected function notificationData($notification): array
    {
        return [
            'id' => (string) $notification->id,
            'title' => $notification->data['title'] ?? $notification->data['case_number'] ?? 'TENACOS',
            'message' => $notification->data['message'] ?? 'Vous avez une nouvelle notification.',
            'url' => $notification->data['url'] ?? route('service-cases.index'),
            'icon' => $notification->data['icon'] ?? 'folder_shared',
        ];
    }

    protected function notificationTypes(): array
    {
        if (Auth::user()?->user_type === User::ROLE_LIVREUR) {
            return [];
        }

        return [
            CustomerServiceCaseNotification::class,
            StockControlNotification::class,
            DgAppointmentNotification::class,
            FinanceCashCountAlertNotification::class,
            FinanceExpenseReportNotification::class,
            FinanceWorkflowNotification::class,
            FinanceExternalLoanDueNotification::class,
        ];
    }

    protected function seenNotificationsSessionKey(): string
    {
        return 'customer_service_notification_push_seen_'.Auth::id();
    }

    protected function initialPushSessionKey(): string
    {
        return 'customer_service_notification_initial_push_shown_'.Auth::id();
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
            ->whereIn('type', $this->notificationTypes())
            ->whereKey($notificationId)
            ->firstOrFail()
            ->markAsRead();

        $this->dispatchBrowserEvent('stock-notification-read');
    }

    public function openNotification(string $notificationId): void
    {
        $notification = Auth::user()->unreadNotifications()
            ->whereIn('type', $this->notificationTypes())
            ->whereKey($notificationId)
            ->firstOrFail();

        $url = $notification->data['url'] ?? null;
        $caseId = $notification->data['case_id'] ?? null;
        $isStockNotification = ($notification->data['module'] ?? null) === 'stock_control';
        $notification->markAsRead();
        $this->dispatchBrowserEvent('stock-notification-read');

        $stockFallback = Auth::user()->user_type === 'ADMINUSER'
            ? route('stock-control.index')
            : route('stock-control.receipts.index');
        $destination = $url ?: ($caseId
            ? route('service-cases.show', $caseId)
            : ($isStockNotification ? $stockFallback : route('service-cases.index')));

        $this->redirect($destination);
    }

    public function render()
    {
        $unread = Auth::user()->unreadNotifications()
            ->whereIn('type', $this->notificationTypes());
        $courierOrders = $this->scheduledCourierOrders();

        return view('livewire.customer-service-notifications', [
            'notificationCount' => (clone $unread)->count() + $courierOrders->count(),
            'notifications' => (clone $unread)->latest()->limit(6)->get(),
            'courierScheduledOrders' => $courierOrders,
        ]);
    }

    protected function scheduledCourierOrders()
    {
        $courier = Auth::user();
        if ($courier?->user_type !== User::ROLE_LIVREUR || ! $courier->active) {
            return Order::query()->whereRaw('1 = 0')->get();
        }

        $courierCountry = strtoupper(trim((string) ($courier->country_code ?: config('services.fedapay.country_code', 'TG'))));
        $defaultCountry = strtoupper((string) config('services.fedapay.country_code', 'TG'));

        return Order::query()
            ->with('costumer')
            ->where('status', 'ordered')
            ->where('brouillon', 1)
            ->where('type', 'PU')
            ->where(function ($query): void {
                $query->where('status_order', false)->orWhereNull('status_order');
            })
            ->where(function ($query): void {
                $query->where('take', false)->orWhereNull('take');
            })
            ->where(function ($query): void {
                $query->whereNull('user_id')->orWhere('user_id', '');
            })
            ->where(function ($query): void {
                $query->whereDate('created_at', today()->toDateString())
                    ->orWhereDate('date_order', '>=', today()->toDateString());
            })
            ->when(filled($courier->country_code), function ($orders) use ($defaultCountry, $courierCountry): void {
                $orders->whereRaw(
                    "UPPER(COALESCE(NULLIF(country_code, ''), NULLIF(shipping_country, ''), ?)) = ?",
                    [$defaultCountry, $courierCountry]
                );
            })
            ->orderBy('date_order')
            ->orderBy('time')
            ->get();
    }
}
