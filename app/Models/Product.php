<?php

namespace App\Models;

use App\Models\OutStock;
use App\Models\EnterStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}
