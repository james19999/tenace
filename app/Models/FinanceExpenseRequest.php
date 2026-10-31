<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceExpenseRequest extends Model
{
    protected $fillable = [
        'reference', 'stock_location_id', 'category_id', 'category_name', 'description', 'amount',
        'beneficiary', 'budget_reference', 'requested_payment_date', 'status', 'approval_route',
        'justification_path', 'justification_note', 'requested_by', 'submitted_at', 'finance_reviewed_by', 'finance_reviewed_at',
        'finance_comment', 'director_decided_by', 'director_decided_at', 'director_comment', 'payment_status',
        'paid_amount', 'paid_at', 'paid_by', 'cancellation_reason',
        'staff_user_id', 'staff_advance_type', 'staff_payroll_period_id', 'commission_user_id', 'commission_period_start', 'commission_period_end',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_amount' => 'integer',
        'requested_payment_date' => 'date',
        'submitted_at' => 'datetime',
        'finance_reviewed_at' => 'datetime',
        'director_decided_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TypeExpensive::class, 'category_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function financeReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finance_reviewed_by');
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_decided_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinanceExpensePayment::class, 'expense_request_id')->orderBy('paid_at');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(FinanceExpenseApproval::class, 'expense_request_id')->orderBy('decided_at');
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function staffPayrollPeriod(): BelongsTo
    {
        return $this->belongsTo(EmployeePayrollPeriod::class, 'staff_payroll_period_id');
    }

    public function commissionMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commission_user_id');
    }
}
