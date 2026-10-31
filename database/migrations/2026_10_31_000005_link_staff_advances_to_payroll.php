<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_staff_advances', function (Blueprint $table): void {
            $table->string('advance_type', 24)->default('loan')->after('amount');
            $table->foreignId('payroll_period_id')->nullable()->after('advance_type')
                ->constrained('employee_payroll_periods')->restrictOnDelete();
            $table->foreignId('employee_payroll_payment_id')->nullable()->unique()->after('payroll_period_id')
                ->constrained('employee_payroll_payments')->restrictOnDelete();
        });

        Schema::table('finance_expense_requests', function (Blueprint $table): void {
            $table->string('staff_advance_type', 24)->nullable()->after('staff_user_id');
            $table->foreignId('staff_payroll_period_id')->nullable()->after('staff_advance_type')
                ->constrained('employee_payroll_periods')->restrictOnDelete();
        });

        DB::table('employee_payroll_payments')->where('kind', 'advance')->orderBy('id')->chunk(100, function ($payments): void {
            foreach ($payments as $payment) {
                if (DB::table('finance_staff_advances')->where('employee_payroll_payment_id', $payment->id)->exists()) {
                    continue;
                }

                $period = DB::table('employee_payroll_periods')->where('id', $payment->period_id)->first();
                if (! $period) {
                    continue;
                }

                DB::table('finance_staff_advances')->insert([
                    'reference' => 'AV-HIST-'.str_pad((string) $payment->id, 8, '0', STR_PAD_LEFT),
                    'user_id' => $period->user_id,
                    'finance_expense_payment_id' => null,
                    'amount' => $payment->amount,
                    'advance_type' => 'salary_advance',
                    'payroll_period_id' => $period->id,
                    'employee_payroll_payment_id' => $payment->id,
                    'repaid_amount' => 0,
                    'granted_on' => substr($payment->paid_at, 0, 10),
                    'reason' => 'Avance sur salaire enregistrée avant le rapprochement des suivis.',
                    'created_by' => $payment->paid_by,
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('finance_staff_advances')->whereNotNull('employee_payroll_payment_id')->delete();

        Schema::table('finance_expense_requests', function (Blueprint $table): void {
            $table->dropForeign(['staff_payroll_period_id']);
            $table->dropColumn(['staff_advance_type', 'staff_payroll_period_id']);
        });

        Schema::table('finance_staff_advances', function (Blueprint $table): void {
            $table->dropForeign(['employee_payroll_payment_id']);
            $table->dropUnique(['employee_payroll_payment_id']);
            $table->dropForeign(['payroll_period_id']);
            $table->dropColumn(['advance_type', 'payroll_period_id', 'employee_payroll_payment_id']);
        });
    }
};
