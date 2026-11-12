<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceExternalLoanRepayment extends Model
{
    protected $fillable = ['loan_id', 'amount', 'paid_on', 'next_payment_on', 'note', 'cash_movement_id', 'created_by'];

    protected $casts = [
        'amount' => 'integer',
        'paid_on' => 'date',
        'next_payment_on' => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(FinanceExternalLoan::class, 'loan_id');
    }

    public function cashMovement(): BelongsTo
    {
        return $this->belongsTo(FinanceCashMovement::class, 'cash_movement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
