<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceProfitabilityPeriod extends Model
{
    protected $fillable = ['period_start', 'period_end', 'status', 'snapshot', 'closed_by', 'closed_at'];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'snapshot' => 'array',
        'closed_at' => 'datetime',
    ];

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
