<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerServiceCaseArchiveAccessRequest extends Model
{
    protected $fillable = [
        'case_id', 'requester_id', 'archive_snapshot_at', 'status', 'request_note',
        'decided_by', 'decision_note', 'decided_at',
    ];

    protected $casts = [
        'archive_snapshot_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function customerServiceCase(): BelongsTo
    {
        return $this->belongsTo(CustomerServiceCase::class, 'case_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
