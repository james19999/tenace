<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeProfile extends Model
{
    protected $fillable = ['user_id', 'tracking_id', 'job_title', 'department', 'hired_on', 'monthly_salary', 'salary_effective_from', 'work_site', 'notes'];

    protected $casts = ['hired_on' => 'date', 'monthly_salary' => 'integer', 'salary_effective_from' => 'date'];

    protected static function booted(): void
    {
        static::creating(function (self $profile): void {
            if (! $profile->tracking_id) {
                do {
                    $profile->tracking_id = 'EMP-'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(12));
                } while (self::query()->where('tracking_id', $profile->tracking_id)->exists());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrollPeriods(): HasMany
    {
        return $this->hasMany(EmployeePayrollPeriod::class, 'user_id', 'user_id')->orderByDesc('period_start');
    }
}
