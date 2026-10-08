<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostumerContactSetting extends Model
{
    protected $fillable = ['default_follow_up_days', 'default_country_calling_code', 'max_follow_ups', 'follow_up_start_date'];
}
