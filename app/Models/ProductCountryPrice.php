<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCountryPrice extends Model
{
    protected $fillable = ['product_id', 'country_code', 'price', 'delivery_fee', 'has_delivery_fee_override', 'is_available'];

    protected $casts = ['price' => 'integer', 'delivery_fee' => 'integer', 'has_delivery_fee_override' => 'boolean', 'is_available' => 'boolean'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
