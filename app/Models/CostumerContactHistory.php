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
        'channel_detail',
        'contacted_at',
        'follow_up_at',
        'responded_at',
        'response',
        'sentiment',
        'follow_up_decision',
        'message_template_id',
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

    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(CostumerContactMessageTemplate::class, 'message_template_id');
    }
}
