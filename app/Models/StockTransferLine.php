<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferLine extends Model
{
    protected $fillable = [
        'transfer_id', 'product_id', 'lot_id', 'quantity_requested', 'quantity_shipped',
        'quantity_received', 'quantity_damaged', 'discrepancy_note',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3',
        'quantity_shipped' => 'decimal:3',
        'quantity_received' => 'decimal:3',
        'quantity_damaged' => 'decimal:3',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
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
