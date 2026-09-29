<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostumerContactMessageTemplate extends Model
{
    protected $fillable = ['channel', 'name', 'body', 'active'];

    protected $casts = ['active' => 'boolean'];
}
