<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCountryPrice extends Model
{
    protected $fillable = ['product_id', 'country_code', 'price', 'is_available'];

    protected $casts = ['price' => 'integer', 'is_available' => 'boolean'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
