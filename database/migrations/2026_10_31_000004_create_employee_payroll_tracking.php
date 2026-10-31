<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table): void {
            $table->unsignedBigInteger('monthly_salary')->nullable()->after('hired_on');
            $table->date('salary_effective_from')->nullable()->after('monthly_salary');
        });

        Schema::create('employee_payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('period_start');
            $table->unsignedBigInteger('salary_amount');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'period_start'], 'employee_payroll_user_period_unique');
            $table->index(['period_start', 'user_id'], 'employee_payroll_period_user_idx');
        });

        Schema::create('employee_payroll_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->uuid('submission_key')->unique();
            $table->foreignId('period_id')->constrained('employee_payroll_periods')->restrictOnDelete();
            $table->string('kind', 16);
            $table->unsignedBigInteger('amount');
            $table->string('payment_reference', 120)->nullable();
            $table->dateTime('paid_at');
            $table->foreignId('paid_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('finance_cash_movement_id')->nullable()->unique()->constrained('finance_cash_movements')->nullOnDelete();
            $table->timestamps();
            $table->index(['period_id', 'paid_at'], 'employee_payroll_payment_period_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_payments');
        Schema::dropIfExists('employee_payroll_periods');
        Schema::table('employee_profiles', function (Blueprint $table): void {
            $table->dropColumn(['monthly_salary', 'salary_effective_from']);
        });
    }
};
