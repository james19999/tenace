<?php

namespace App\Models;

use App\Models\OutStock;
use App\Models\EnterStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable=['name', 'commission_amount','price','price_by','qts_seuil','qt_initial','price_market','high_price' ,'img' ,'description'];

    public function enterstocks() {
        return $this->hasMany(EnterStock::class,'product_id');
    }
    public function outstocks() {
        return $this->hasMany(OutStock::class);
    }

    public function stockLots(): HasMany
    {
        return $this->hasMany(StockLot::class);
    }

    public function stockMovementLines(): HasMany
    {
        return $this->hasMany(StockMovementLine::class);
    }

    public function stockTransferLines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class);
    }

    public function stockReceiptLines(): HasMany
    {
        return $this->hasMany(StockReceiptLine::class);
    }

    public function stockCountLines(): HasMany
    {
        return $this->hasMany(StockCountLine::class);
    }

    public function stockAdjustmentLines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLine::class);
    }

    public function countryPrices(): HasMany
    {
        return $this->hasMany(ProductCountryPrice::class);
    }

    public function publicOrderLinks(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(PublicOrderLink::class, 'public_order_link_products')
            ->withPivot('sort_order')->withTimestamps();
    }
}
