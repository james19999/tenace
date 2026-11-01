<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceEquipmentPurchaseRequest extends Model
{
    protected $fillable = [
        'reference', 'title', 'assignment_notes', 'assigned_to', 'created_by', 'status', 'needed_items',
        'requested_amount', 'approved_amount', 'approved_by', 'approved_at', 'cash_issued', 'issued_by',
        'issued_at', 'spent_amount', 'returned_amount', 'report_note', 'receipt_paths', 'submitted_at',
        'reviewed_by', 'reviewed_at', 'review_note', 'cash_movement_id', 'return_movement_id',
    ];

    protected $casts = [
        'requested_amount' => 'integer', 'approved_amount' => 'integer', 'cash_issued' => 'integer',
        'spent_amount' => 'integer', 'returned_amount' => 'integer', 'receipt_paths' => 'array',
        'approved_at' => 'datetime', 'issued_at' => 'datetime', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime',
    ];

    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
