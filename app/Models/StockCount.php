<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCount extends Model
{
    protected $fillable = [
        'count_number', 'location_id', 'status', 'blind_count', 'is_opening_balance', 'scheduled_at', 'started_at',
        'submitted_at', 'approved_at', 'completed_at', 'created_by', 'counter_id', 'reviewed_by',
        'approved_by', 'scope', 'notes',
    ];

    protected $casts = [
        'blind_count' => 'boolean',
        'is_opening_balance' => 'boolean',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class, 'count_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counter_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
