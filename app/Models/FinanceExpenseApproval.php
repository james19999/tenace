<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceExpenseApproval extends Model
{
    protected $fillable = ['expense_request_id', 'stage', 'decision', 'decided_by', 'comment', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime'];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(FinanceExpenseRequest::class, 'expense_request_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
