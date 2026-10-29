<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceCashRemittance extends Model
{
    protected $fillable = [
        'reference', 'stock_location_id', 'period_start', 'period_end', 'expected_amount', 'counted_amount',
        'handed_over_amount', 'received_amount', 'destination', 'status', 'shop_manager_id',
        'shop_manager_signed_at', 'collector_id', 'collector_signed_at', 'received_by', 'received_at',
        'bank_slip_reference', 'bank_slip_path', 'signed_voucher_path', 'discrepancy_note', 'resolution_note', 'resolved_by',
        'resolved_at', 'paper_voucher_reference', 'notes', 'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'expected_amount' => 'integer',
        'counted_amount' => 'integer',
        'handed_over_amount' => 'integer',
        'received_amount' => 'integer',
        'shop_manager_signed_at' => 'datetime',
        'collector_signed_at' => 'datetime',
        'received_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function shopManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_manager_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(FinanceCashMovement::class, 'remittance_id');
    }
}
