<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EnterStock extends Model
{
    use HasFactory;
    protected $fillable= [
         'product_id',
         'qt_stock',
    ];

    public function product () {
        return $this->belongsTo(Product::class,'product_id');
    }
}
