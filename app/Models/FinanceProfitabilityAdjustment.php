<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceProfitabilityAdjustment extends Model
{
    protected $fillable = [
        'period_start', 'kind', 'nature', 'amount', 'description', 'status', 'created_by',
        'updated_by', 'voided_by', 'voided_at', 'void_reason',
    ];

    protected $casts = [
        'period_start' => 'date',
        'amount' => 'integer',
        'voided_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
