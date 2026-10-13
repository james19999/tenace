<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferEventLine extends Model
{
    protected $fillable = [
        'event_id', 'transfer_line_id', 'product_id', 'lot_id', 'quantity', 'quantity_damaged', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'quantity_damaged' => 'decimal:3',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(StockTransferEvent::class, 'event_id');
    }

    public function transferLine(): BelongsTo
    {
        return $this->belongsTo(StockTransferLine::class, 'transfer_line_id');
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
