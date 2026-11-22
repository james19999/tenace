<?php

namespace App\Models\Orders;

use App\Models\Costumer;
use App\Models\OrderCourierHandoff;
use App\Models\OrderPayment;
use App\Models\PublicOrderLink;
use App\Models\TransportCompany;
use App\Models\User;
use App\Notifications\CanceledOrderNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $order): void {
            if ($order->status === 'delivered' && ($order->isDirty('status') || ! $order->delivered_at)) {
                $order->delivered_at = now();
            }

            if ($order->status === 'canceled' && ($order->isDirty('status') || ! $order->canceled_at)) {
                $order->canceled_at = now();
                $order->canceled_at_is_estimated = false;
            } elseif ($order->status !== 'canceled' && $order->isDirty('status')) {
                $order->canceled_at = null;
                $order->canceled_at_is_estimated = false;
            }

            if (in_array($order->status, ['delivered', 'canceled'], true)) {
                $order->delivery_waiting = false;
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

            if ($order->wasRecentlyCreated || $order->wasChanged([
                'status', 'status_order', 'take', 'user_id', 'date_order', 'time', 'brouillon', 'type', 'country_code', 'shipping_country',
            ])) {
                $orderId = (int) $order->getKey();
                $actorId = Auth::id() ? (int) Auth::id() : null;
                DB::afterCommit(static function () use ($orderId, $actorId): void {
                    try {
                        app(\App\Services\CourierPushNotificationService::class)
                            ->notifyOrderAvailability($orderId, $actorId);
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                });
            }

            if ($order->wasChanged(['status', 'partner_order_status'])
                && ($order->status === 'canceled' || $order->partner_order_status === 'canceled')) {
                $orderId = (int) $order->getKey();
                DB::afterCommit(static function () use ($orderId): void {
                    $canceledOrder = self::query()->find($orderId);
                    if (! $canceledOrder) {
                        return;
                    }

                    User::query()->where('active', true)
                        ->whereIn('user_type', [User::ROLE_CALL_CENTER, User::ROLE_SECRETARY])
                        ->get()
                        ->each(fn (User $recipient) => $recipient->notify(new CanceledOrderNotification($canceledOrder)));
                });
            }
        });
    }

    protected $casts = [
        'assigned_at' => 'datetime',
        'delivered_at' => 'datetime',
        'canceled_at' => 'datetime',
        'canceled_at_is_estimated' => 'boolean',
        'is_wholesale' => 'boolean',
        'delivery_waiting' => 'boolean',
    ];

    protected $fillable = [
        'user_id',
        'costumer_id',
        'subtotal',
        'tax',
        'total',
        'type',
        'is_wholesale',
        'code',
        'time',
        'brouillon',
        'created_user',
        'avis',
        'remis',
        'montant',
        'paid_amount',
        'delivery_mode',
        'delivery_address',
        'delivery_phone',
        'transport_company_id',
        'shipping_country',
        'shipping_city',
        'order_by',
        'date_order',
        'delivered_at',
        'canceled_at',
        'canceled_at_is_estimated',
        'country_code',
        'partner_id',
        'public_order_link_id',
        'payment_timing',
        'requested_payment_method',
        'public_tracking_token',
        'partner_order_status',
        'delivery_location_url',
        'delivery_waiting',
    ];

    public function costumer()
    {

        return $this->belongsTo(Costumer::class, 'costumer_id');
    }

    public function user()
    {

        return $this->belongsTo(User::class, 'user_id');
    }

    public function createduser()
    {

        return $this->belongsTo(User::class, 'created_user');
    }

    public function orderItems()
    {
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
