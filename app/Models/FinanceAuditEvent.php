<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceAuditEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['subject_type', 'subject_id', 'action', 'before_values', 'after_values', 'reason', 'user_id', 'created_at'];

    protected $casts = [
        'before_values' => 'array',
        'after_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
