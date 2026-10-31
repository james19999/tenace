<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeePayrollPeriod extends Model
{
    protected $fillable = ['reference', 'user_id', 'period_start', 'salary_amount', 'note', 'created_by'];

    protected $casts = ['period_start' => 'date', 'salary_amount' => 'integer'];

    public function employee(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function payments(): HasMany { return $this->hasMany(EmployeePayrollPayment::class, 'period_id')->orderBy('paid_at'); }
    public function staffAdvances(): HasMany { return $this->hasMany(FinanceStaffAdvance::class, 'payroll_period_id')->orderBy('granted_on'); }
}
