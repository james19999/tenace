<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayrollPayment extends Model
{
    protected $fillable = [
        'reference', 'submission_key', 'period_id', 'kind', 'amount', 'payment_reference', 'paid_at', 'paid_by', 'finance_cash_movement_id',
    ];

    protected $casts = ['amount' => 'integer', 'paid_at' => 'datetime'];

    public function period(): BelongsTo { return $this->belongsTo(EmployeePayrollPeriod::class, 'period_id'); }
    public function payer(): BelongsTo { return $this->belongsTo(User::class, 'paid_by'); }
    public function cashMovement(): BelongsTo { return $this->belongsTo(FinanceCashMovement::class, 'finance_cash_movement_id'); }
}
