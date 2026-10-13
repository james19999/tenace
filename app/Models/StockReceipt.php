<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class StockReceipt extends Model
{
    protected $fillable = [
        'receipt_number', 'location_id', 'status', 'origin_name', 'source_reference',
        'movement_id', 'created_by', 'validated_by', 'occurred_at', 'validated_at', 'notes',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'movement_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockReceiptLine::class, 'receipt_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(StockReceiptEvent::class, 'receipt_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(StockAttachment::class, 'subject');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
