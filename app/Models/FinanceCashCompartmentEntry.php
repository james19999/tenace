<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceCashCompartmentEntry extends Model
{
    protected $fillable = [
        'compartment_id', 'cash_movement_id', 'entry_type', 'amount', 'percentage_snapshot',
        'source_key', 'description', 'occurred_at', 'created_by',
    ];

    protected $casts = [
        'amount' => 'integer', 'percentage_snapshot' => 'integer', 'occurred_at' => 'datetime',
    ];

    public function compartment(): BelongsTo
    {
        return $this->belongsTo(FinanceCashCompartment::class, 'compartment_id');
    }

    public function cashMovement(): BelongsTo
    {
        return $this->belongsTo(FinanceCashMovement::class, 'cash_movement_id');
    }
}
