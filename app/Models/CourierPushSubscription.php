<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierPushSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'endpoint_hash',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'last_used_at',
    ];

    protected $casts = [
        'endpoint' => 'encrypted',
        'public_key' => 'encrypted',
        'auth_token' => 'encrypted',
        'last_used_at' => 'datetime',
    ];
}
