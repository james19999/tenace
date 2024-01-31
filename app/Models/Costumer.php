<?php

namespace App\Models;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
class Costumer extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $guard = "companiecostumer";
    protected  $fillable=[
          'name',
          'phone',
          'email',
          'adresse',
          'user_id',

    ];


    public function orders(){
        return $this->hasMany(Order::class);
    }
}
