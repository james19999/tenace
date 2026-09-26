<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostumerContactHistory extends Model
{
    protected $fillable = [
        'costumer_id',
        'user_id',
        'contact_type',
        'channel',
        'contacted_at',
        'follow_up_at',
        'responded_at',
        'response',
        'notes',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
        'follow_up_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function costumer(): BelongsTo
    {
        return $this->belongsTo(Costumer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
