<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceStaffAdvanceRepayment extends Model
{
    protected $fillable = ['advance_id', 'employee_payroll_payment_id', 'amount', 'received_at', 'received_by'];
    protected $casts = ['amount' => 'integer', 'received_at' => 'datetime'];

    public function advance(): BelongsTo { return $this->belongsTo(FinanceStaffAdvance::class, 'advance_id'); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
}
