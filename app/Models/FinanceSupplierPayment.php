<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceSupplierPayment extends Model
{
    protected $fillable = ['supplier_invoice_id', 'amount', 'payment_reference', 'proof_path', 'paid_by', 'paid_at'];

    protected $casts = ['amount' => 'integer', 'paid_at' => 'datetime'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FinanceSupplierInvoice::class, 'supplier_invoice_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
