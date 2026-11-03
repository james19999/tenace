<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceTontineContributionRequest extends Model
{
    protected $fillable = [
        'reference', 'tontine_id', 'requested_by', 'amount', 'note', 'status', 'requested_at',
        'paid_by', 'paid_at', 'cash_movement_id', 'tontine_movement_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'requested_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(FinanceTontine::class, 'tontine_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function cashMovement(): BelongsTo
    {
        return $this->belongsTo(FinanceCashMovement::class, 'cash_movement_id');
    }

    public function tontineMovement(): BelongsTo
    {
        return $this->belongsTo(FinanceTontineMovement::class, 'tontine_movement_id');
    }
}
