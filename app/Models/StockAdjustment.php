<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    protected $fillable = [
        'adjustment_number', 'status', 'reason_code', 'reason', 'count_id', 'movement_id',
        'requested_by', 'approved_by', 'submitted_at', 'approved_at', 'posted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLine::class, 'adjustment_id');
    }

    public function count(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'count_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'movement_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
