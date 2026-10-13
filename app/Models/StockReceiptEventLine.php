<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReceiptEventLine extends Model
{
    protected $fillable = [
        'event_id', 'receipt_line_id', 'product_id', 'lot_id', 'quantity_received',
        'quantity_damaged', 'unit_cost', 'discrepancy_note',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:3',
        'quantity_damaged' => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(StockReceiptEvent::class, 'event_id');
    }

    public function receiptLine(): BelongsTo
    {
        return $this->belongsTo(StockReceiptLine::class, 'receipt_line_id');
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
