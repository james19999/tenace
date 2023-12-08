<?php

namespace App\Models\Orders;

use App\Models\User;
use App\Models\Costumer;
use App\Models\Orders\OrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable=[
        'user_id',
        'costumer_id',
        'subtotal',
        'tax',
        'total',
        'code',
        'time',
        'brouillon',
        'created_user',
        'avis',
        'remis',
        'montant',
        'type'
    ];


    public function costumer(){

        return $this->belongsTo(Costumer::class,'costumer_id');
    }

    public function user () {

        return $this->belongsTo(User::class,'user_id');
    }
    public function createduser () {

        return $this->belongsTo(User::class,'created_user');
    }


    public function orderItems(){
        return $this->hasMany(OrderItem::class);
    }
}
