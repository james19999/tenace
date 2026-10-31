<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceCommissionSettlement extends Model
{
    protected $fillable = ['reference', 'user_id', 'amount', 'period_start', 'period_end', 'paid_at', 'paid_by'];
    protected $casts = ['amount' => 'integer', 'period_start' => 'date', 'period_end' => 'date', 'paid_at' => 'datetime'];

    public function employee(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function payer(): BelongsTo { return $this->belongsTo(User::class, 'paid_by'); }
}
