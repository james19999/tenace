<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockAlert extends Model
{
    protected $fillable = [
        'type', 'severity', 'status', 'deduplication_key', 'subject_type', 'subject_id',
        'location_id', 'product_id', 'assigned_to', 'title', 'message', 'details', 'due_at',
        'resolved_at', 'resolved_by',
    ];

    protected $casts = [
        'details' => 'array',
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
