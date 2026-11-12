<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceExternalLoan extends Model
{
    protected $fillable = [
        'reference', 'lender_name', 'contract_reference', 'received_amount', 'total_repayable',
        'initial_repaid_amount', 'amount_repaid', 'received_on', 'first_payment_on', 'repayment_frequency', 'next_payment_on', 'installment_amount', 'status', 'notes', 'cash_movement_id', 'created_by',
    ];

    protected $casts = [
        'received_amount' => 'integer',
        'total_repayable' => 'integer',
        'initial_repaid_amount' => 'integer',
        'amount_repaid' => 'integer',
        'installment_amount' => 'integer',
        'received_on' => 'date',
        'first_payment_on' => 'date',
        'next_payment_on' => 'date',
    ];

    public function repayments(): HasMany
    {
        return $this->hasMany(FinanceExternalLoanRepayment::class, 'loan_id')->orderBy('paid_on')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cashMovement(): BelongsTo
    {
        return $this->belongsTo(FinanceCashMovement::class, 'cash_movement_id');
    }

    public function getRemainingAmountAttribute(): int
    {
        return max(0, (int) $this->total_repayable - (int) $this->amount_repaid);
    }
}
