<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReceiptLine extends Model
{
    protected $fillable = [
        'receipt_id', 'product_id', 'lot_id', 'lot_code', 'manufactured_at', 'expires_at', 'quantity_expected', 'quantity_received',
        'quantity_damaged', 'unit_cost', 'discrepancy_note',
    ];

    protected $casts = [
        'quantity_expected' => 'decimal:3',
        'quantity_received' => 'decimal:3',
        'quantity_damaged' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'manufactured_at' => 'date',
        'expires_at' => 'date',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(StockReceipt::class, 'receipt_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(StockLot::class, 'lot_id');
    }
}
