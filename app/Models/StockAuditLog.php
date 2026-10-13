<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAuditLog extends Model
{
    protected $fillable = [
        'user_id', 'actor_role', 'action', 'subject_type', 'subject_id', 'before_values',
        'after_values', 'reason', 'origin', 'result', 'request_id',
    ];

    protected $casts = [
        'before_values' => 'array',
        'after_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
