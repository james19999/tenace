<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class StockReceiptEvent extends Model
{
    protected $fillable = [
        'event_number', 'receipt_id', 'status', 'movement_id', 'received_by', 'approved_by',
        'occurred_at', 'approved_at', 'notes',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(StockReceipt::class, 'receipt_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'movement_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockReceiptEventLine::class, 'event_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(StockAttachment::class, 'subject');
    }
}
