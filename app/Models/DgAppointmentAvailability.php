<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DgAppointmentAvailability extends Model
{
    protected $table = 'dg_appointment_availability_ranges';

    protected $fillable = ['dg_user_id', 'weekday', 'starts_at', 'ends_at'];
}
