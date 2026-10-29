<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceCashClosure extends Model
{
    protected $fillable = [
        'closing_date', 'theoretical_amount', 'counted_amount', 'variance_amount', 'denominations',
        'status', 'count_note', 'resolution_note', 'counted_by', 'counted_at', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'closing_date' => 'date',
        'theoretical_amount' => 'integer',
        'counted_amount' => 'integer',
        'variance_amount' => 'integer',
        'denominations' => 'array',
        'counted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
