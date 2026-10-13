<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentLine extends Model
{
    protected $fillable = [
        'adjustment_id', 'product_id', 'location_id', 'lot_id', 'quantity_before',
        'quantity_counted', 'quantity_delta', 'unit_cost', 'notes',
    ];

    protected $casts = [
        'quantity_before' => 'decimal:3',
        'quantity_counted' => 'decimal:3',
        'quantity_delta' => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(StockLot::class, 'lot_id');
    }
}
