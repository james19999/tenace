<?php

namespace App\Models;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockMovement extends Model
{
    protected $fillable = [
        'movement_number', 'type', 'status', 'transfer_id', 'order_id', 'reverses_movement_id',
        'source_type', 'source_reference', 'occurred_at', 'origin', 'reason', 'notes', 'metadata',
        'created_by', 'submitted_by', 'approved_by', 'posted_by', 'submitted_at', 'approved_at',
        'posted_at', 'reversed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(StockMovementLine::class, 'movement_id');
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_movement_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reverses_movement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
