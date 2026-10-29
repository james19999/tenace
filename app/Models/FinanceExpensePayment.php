<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceExpensePayment extends Model
{
    protected $fillable = [
        'expense_request_id', 'amount', 'custody_account', 'stock_location_id', 'payment_reference',
        'proof_path', 'paid_by', 'paid_at',
    ];

    protected $casts = ['amount' => 'integer', 'paid_at' => 'datetime'];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(FinanceExpenseRequest::class, 'expense_request_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }
}
