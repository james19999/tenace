<?php

namespace App\Models\Orders;

use App\Models\User;
use App\Models\Costumer;
use App\Models\TransportCompany;
use App\Models\OrderPayment;
use App\Models\OrderCourierHandoff;
use App\Models\PublicOrderLink;
use App\Models\Orders\OrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $order): void {
            if ($order->status === 'delivered' && ($order->isDirty('status') || ! $order->delivered_at)) {
                $order->delivered_at = now();
            }
        });

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
        'delivered_at' => 'datetime',
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
        'paid_amount',
        'delivery_mode',
        'transport_company_id',
        'shipping_country',
        'shipping_city',
        'order_by',
        'date_order',
        'delivered_at',
        'country_code',
        'partner_id',
        'public_order_link_id',
        'payment_timing',
        'requested_payment_method',
        'public_tracking_token',
        'partner_order_status',
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

    public function transportCompany()
    {
        return $this->belongsTo(TransportCompany::class);
    }

    public function deliveryPhotos()
    {
        return $this->hasMany(OrderDeliveryPhoto::class)->latest();
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function publicOrderLink()
    {
        return $this->belongsTo(PublicOrderLink::class);
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function courierHandoffs()
    {
        return $this->hasMany(OrderCourierHandoff::class)->latest();
    }
}
