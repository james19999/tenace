<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceSupplierInvoice extends Model
{
    protected $fillable = [
        'supplier_name', 'invoice_number', 'supplier_invoice_number', 'invoice_date', 'due_date', 'amount', 'stock_receipt_id',
        'status', 'payment_status', 'paid_amount', 'invoice_path', 'notes', 'review_comment',
        'created_by', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'invoice_date' => 'date', 'due_date' => 'date', 'amount' => 'integer', 'paid_amount' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(StockReceipt::class, 'stock_receipt_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinanceSupplierPayment::class, 'supplier_invoice_id')->orderBy('paid_at');
    }
}
