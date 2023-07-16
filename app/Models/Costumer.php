<?php

namespace App\Models;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Costumer extends Model
{
    use HasFactory;

    protected  $fillable=[
          'name',
          'phone',
          'email',
          'adresse',

    ];


    public function orders(){
        return $this->hasMany(Order::class);
    }
}
