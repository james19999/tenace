<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCountryPriceTier extends Model
{
    protected $fillable = ['product_country_price_id', 'min_quantity', 'unit_price'];

    protected $casts = ['min_quantity' => 'integer', 'unit_price' => 'integer'];

    public function countryPrice(): BelongsTo
    {
        return $this->belongsTo(ProductCountryPrice::class, 'product_country_price_id');
    }
}
