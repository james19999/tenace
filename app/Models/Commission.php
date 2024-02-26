<?php

namespace App\Models;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;
    protected $fillable = [
        "amount",
        "user_id",
        "fixed",
        "total",
        "order_id"
    ];

    public function order (){
        return $this->belongsTo(Order::class,'order_id');
    }
}
