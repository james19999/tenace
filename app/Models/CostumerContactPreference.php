<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostumerContactPreference extends Model
{
    protected $fillable = [
        'costumer_id',
        'calling_code',
        'next_follow_up_at',
        'do_not_contact_at',
        'do_not_contact_reason',
        'updated_by',
    ];

    protected $casts = [
        'do_not_contact_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
    ];

    public function costumer(): BelongsTo
    {
        return $this->belongsTo(Costumer::class);
    }
}
