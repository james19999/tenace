<?php

namespace App\Notifications;

use App\Models\CustomerServiceCase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CustomerServiceCaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CustomerServiceCase $case,
        public string $event,
        public string $message,
        public ?string $reminderType = null,
        public ?string $reminderFor = null,
        public ?int $milestoneId = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'case_id' => $this->case->id,
            'case_number' => $this->case->case_number,
            'customer_name' => $this->case->customer?->name,
            'event' => $this->event,
            'message' => $this->message,
            'reminder_type' => $this->reminderType,
            'reminder_for' => $this->reminderFor,
            'milestone_id' => $this->milestoneId,
            'url' => route('service-cases.show', $this->case->id),
        ];
    }
}
