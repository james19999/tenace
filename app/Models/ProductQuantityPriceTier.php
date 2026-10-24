<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductQuantityPriceTier extends Model
{
    protected $fillable = ['product_id', 'min_quantity', 'unit_price'];

    protected $casts = ['min_quantity' => 'integer', 'unit_price' => 'integer'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
