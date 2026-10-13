<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountLine extends Model
{
    protected $fillable = [
        'count_id', 'product_id', 'lot_id', 'expected_quantity', 'counted_quantity',
        'second_count_quantity', 'condition', 'discrepancy_note',
    ];

    protected $casts = [
        'expected_quantity' => 'decimal:3',
        'counted_quantity' => 'decimal:3',
        'second_count_quantity' => 'decimal:3',
    ];

    public function count(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'count_id');
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
