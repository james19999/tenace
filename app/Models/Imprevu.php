<?php

namespace App\Models;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Imprevu extends Model
{
    use HasFactory;
    protected $fillable = [
        "amount",
        "fixed",
        "total",
        "order_id"
    ];

    public function order (){
        return $this->belongsTo(Order::class,'order_id');
    }
}
