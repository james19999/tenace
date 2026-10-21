<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DgAppointment extends Model
{
    protected $fillable = [
        'dg_user_id', 'created_by', 'visitor_name', 'visitor_phone', 'subject',
        'appointment_date', 'starts_at', 'ends_at',
    ];

    protected $casts = ['appointment_date' => 'date'];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
