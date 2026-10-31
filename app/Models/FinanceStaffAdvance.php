<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceStaffAdvance extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'finance_expense_payment_id', 'employee_payroll_payment_id', 'amount', 'advance_type', 'payroll_period_id',
        'repaid_amount', 'granted_on', 'reason', 'created_by',
    ];
    protected $casts = ['amount' => 'integer', 'repaid_amount' => 'integer', 'granted_on' => 'date'];

    public function employee(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function payrollPeriod(): BelongsTo { return $this->belongsTo(EmployeePayrollPeriod::class, 'payroll_period_id'); }
    public function financeExpensePayment(): BelongsTo { return $this->belongsTo(FinanceExpensePayment::class, 'finance_expense_payment_id'); }
    public function employeePayrollPayment(): BelongsTo { return $this->belongsTo(EmployeePayrollPayment::class, 'employee_payroll_payment_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function repayments(): HasMany { return $this->hasMany(FinanceStaffAdvanceRepayment::class, 'advance_id')->orderBy('received_at'); }
}
