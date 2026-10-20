<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCountryPrice extends Model
{
    protected $fillable = ['product_id', 'country_code', 'price', 'delivery_fee', 'has_delivery_fee_override', 'is_available'];

    protected $casts = ['price' => 'integer', 'delivery_fee' => 'integer', 'has_delivery_fee_override' => 'boolean', 'is_available' => 'boolean'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function quantityTiers(): HasMany
    {
        return $this->hasMany(ProductCountryPriceTier::class)->orderBy('min_quantity');
    }

    public function unitPriceForQuantity(int $quantity): int
    {
        $tiers = $this->relationLoaded('quantityTiers')
            ? $this->quantityTiers
            : $this->quantityTiers()->get();

        $tier = $tiers->filter(fn (ProductCountryPriceTier $tier) => $tier->min_quantity <= $quantity)
            ->sortByDesc('min_quantity')->first();

        return (int) ($tier?->unit_price ?? $this->price);
    }
}
