<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DgAppointmentCalendar extends Model
{
    protected $fillable = ['dg_user_id'];

    public function dg(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dg_user_id');
    }
}
