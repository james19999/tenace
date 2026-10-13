<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class StockTransferEvent extends Model
{
    protected $fillable = ['event_number', 'transfer_id', 'type', 'movement_id', 'user_id', 'occurred_at', 'notes'];

    protected $casts = ['occurred_at' => 'datetime'];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'movement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferEventLine::class, 'event_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(StockAttachment::class, 'subject');
    }
}
