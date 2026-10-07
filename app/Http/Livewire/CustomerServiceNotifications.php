<?php

namespace App\Http\Livewire;

use App\Notifications\CustomerServiceCaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CustomerServiceNotifications extends Component
{
    public bool $open = false;

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

        $caseId = $notification->data['case_id'] ?? null;
        $notification->markAsRead();

        $this->redirect($caseId
            ? route('service-cases.show', $caseId)
            : route('service-cases.index'));
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
