<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovementLine extends Model
{
    protected $fillable = [
        'movement_id', 'product_id', 'location_id', 'lot_id', 'quantity_delta', 'unit_cost', 'unit', 'notes',
    ];

    protected $casts = [
        'quantity_delta' => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'movement_id');
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
