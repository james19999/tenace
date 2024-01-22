<?php

namespace App\Models\Orders;

use App\Models\Product;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;


    protected $fillable=[
        'product_id',
        'order_id',
        'price',
        'quantity',
        'high_price',
  ];

    public function order(){
        return $this->belongsTo(Order::class,'order_id');
    }

    public function  product(){
        return $this->belongsTo(Product::class,'product_id');
    }
}
