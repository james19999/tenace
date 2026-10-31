<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceCashMovement extends Model
{
    protected $fillable = [
        'entry_number', 'movement_type', 'from_custody', 'to_custody', 'amount', 'stock_location_id',
        'remittance_id', 'source_type', 'source_id', 'source_key', 'source_reference', 'occurred_at',
        'description', 'metadata', 'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function remittance(): BelongsTo
    {
        return $this->belongsTo(FinanceCashRemittance::class, 'remittance_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
