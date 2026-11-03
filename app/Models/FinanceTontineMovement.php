<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinanceTontineMovement extends Model
{
    protected $fillable = [
        'reference', 'submission_key', 'tontine_id', 'type', 'due_on', 'amount', 'occurred_at',
        'proof_path', 'note', 'cash_movement_id', 'created_by',
    ];

    protected $casts = [
        'due_on' => 'date',
        'amount' => 'integer',
        'occurred_at' => 'datetime',
    ];

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(FinanceTontine::class, 'tontine_id');
    }

    public function cashMovement(): BelongsTo
    {
        return $this->belongsTo(FinanceCashMovement::class, 'cash_movement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contributionRequest(): HasOne
    {
        return $this->hasOne(FinanceTontineContributionRequest::class, 'tontine_movement_id');
    }
}
