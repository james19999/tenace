<?php

namespace App\Models;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCourierHandoff extends Model
{
    protected $fillable = [
        'order_id', 'partner_id', 'courier_id', 'handed_over_by', 'received_by_courier',
        'status', 'handed_over_at', 'received_at', 'notes',
    ];

    protected $casts = ['handed_over_at' => 'datetime', 'received_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }
}
