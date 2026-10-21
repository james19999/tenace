<?php

namespace App\Notifications;

use App\Models\DgAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DgAppointmentNotification extends Notification
{
    use Queueable;

    public function __construct(public DgAppointment $appointment, public string $requesterName)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'module' => 'dg_appointment',
            'appointment_id' => $this->appointment->id,
            'title' => 'Nouveau rendez-vous avec le DG',
            'message' => 'Rendez-vous avec '.$this->appointment->visitor_name.' le '
                .$this->appointment->appointment_date->format('d/m/Y').' de '
                .substr($this->appointment->starts_at, 0, 5).' à '.substr($this->appointment->ends_at, 0, 5)
                .' (planifié par '.$this->requesterName.').',
            'url' => route('dg-appointments.index', ['tab' => 'upcoming']),
            'icon' => 'event_available',
        ];
    }
}
