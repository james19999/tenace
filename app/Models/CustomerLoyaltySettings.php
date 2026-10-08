<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerLoyaltySettings extends Model
{
    protected $fillable = ['rules', 'updated_by'];
    protected $casts = ['rules' => 'array'];
}
