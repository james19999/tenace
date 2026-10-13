<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLot extends Model
{
    protected $fillable = [
        'product_id', 'lot_code', 'manufactured_at', 'expires_at', 'status', 'notes',
    ];

    protected $casts = [
        'manufactured_at' => 'date',
        'expires_at' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movementLines(): HasMany
    {
        return $this->hasMany(StockMovementLine::class, 'lot_id');
    }
}
