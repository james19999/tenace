<?php

namespace App\Models;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomerServiceCase extends Model
{
    protected $fillable = [
        'case_number', 'costumer_id', 'order_id', 'product_id', 'case_type', 'purchase_date',
        'description', 'priority', 'status', 'assigned_to', 'created_by', 'next_follow_up_at',
        'resolution', 'resolution_result', 'customer_satisfaction', 'satisfaction_comment', 'closure_reason', 'resolved_at', 'closed_at',
        'archived_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'next_follow_up_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Costumer::class, 'costumer_id'); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function activities(): HasMany { return $this->hasMany(CustomerServiceCaseActivity::class, 'case_id')->orderBy('occurred_at')->orderBy('id'); }
    public function attachments(): HasMany { return $this->hasMany(CustomerServiceCaseAttachment::class, 'case_id')->latest(); }
    public function supportPlan(): HasOne { return $this->hasOne(CustomerServiceSupportPlan::class, 'case_id'); }
}
