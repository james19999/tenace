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

    protected static function booted(): void
    {
        static::saved(function (self $order): void {
            if (($order->wasRecentlyCreated || $order->wasChanged(['status', 'total', 'costumer_id', 'date_order'])) && is_numeric($order->costumer_id)) {
                try {
                    app(\App\Services\CustomerLoyaltyService::class)->refreshCustomer((int) $order->costumer_id);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
        });
    }

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    protected $fillable=[
        'user_id',
        'costumer_id',
        'subtotal',
        'tax',
        'total',
        'type',
        'code',
        'time',
        'brouillon',
        'created_user',
        'avis',
        'remis',
        'montant',
        'order_by',
        'date_order',
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
