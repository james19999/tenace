<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $fillable = [
        'transfer_number', 'source_location_id', 'destination_location_id', 'status', 'due_at',
        'requested_by', 'authorized_by', 'shipped_by', 'received_by', 'submitted_at',
        'authorized_at', 'shipped_at', 'received_at', 'closed_at', 'reason', 'notes',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'submitted_at' => 'datetime',
        'authorized_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'source_location_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'destination_location_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class, 'transfer_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'transfer_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(StockTransferEvent::class, 'transfer_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
