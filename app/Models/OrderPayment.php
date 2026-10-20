<?php

namespace App\Models;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $fillable = [
        'order_id', 'stage', 'method', 'amount', 'status', 'transaction_reference',
        'gateway', 'gateway_transaction_id', 'gateway_reference', 'gateway_method', 'gateway_status',
        'proof_path', 'collected_by', 'confirmed_by', 'collected_at', 'confirmed_at', 'notes',
    ];

    protected $casts = [
        'amount' => 'integer',
        'collected_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
