<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('finance_expense_requests', 'staff_repayment_method')) {
            Schema::table('finance_expense_requests', function (Blueprint $table): void {
                $table->string('staff_repayment_method', 24)->nullable()->after('staff_payroll_period_id');
            });
        }
        if (! Schema::hasColumn('finance_expense_requests', 'staff_repayment_value')) {
            Schema::table('finance_expense_requests', function (Blueprint $table): void {
                $table->decimal('staff_repayment_value', 12, 2)->nullable()->after('staff_repayment_method');
            });
        }

        if (! Schema::hasColumn('finance_staff_advances', 'repayment_method')) {
            Schema::table('finance_staff_advances', function (Blueprint $table): void {
                $table->string('repayment_method', 24)->nullable()->after('advance_type');
            });
        }
        if (! Schema::hasColumn('finance_staff_advances', 'repayment_value')) {
            Schema::table('finance_staff_advances', function (Blueprint $table): void {
                $table->decimal('repayment_value', 12, 2)->nullable()->after('repayment_method');
            });
        }

        if (! Schema::hasColumn('finance_staff_advance_repayments', 'employee_payroll_payment_id')) {
            Schema::table('finance_staff_advance_repayments', function (Blueprint $table): void {
                $table->unsignedBigInteger('employee_payroll_payment_id')->nullable()->after('advance_id');
            });
        }

        $foreignKeyName = 'fin_stf_repay_payroll_fk';
        $foreignKeyExists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'finance_staff_advance_repayments')
            ->where('CONSTRAINT_NAME', $foreignKeyName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if (! $foreignKeyExists) {
            Schema::table('finance_staff_advance_repayments', function (Blueprint $table) use ($foreignKeyName): void {
                $table->foreign('employee_payroll_payment_id', $foreignKeyName)
                    ->references('id')->on('employee_payroll_payments')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('employee_payroll_payments', 'net_amount')) {
            Schema::table('employee_payroll_payments', function (Blueprint $table): void {
                $table->unsignedBigInteger('net_amount')->nullable()->after('amount');
            });
        }
        if (! Schema::hasColumn('employee_payroll_payments', 'loan_deduction_amount')) {
            Schema::table('employee_payroll_payments', function (Blueprint $table): void {
                $table->unsignedBigInteger('loan_deduction_amount')->default(0)->after('net_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employee_payroll_payments', 'net_amount')) {
            Schema::table('employee_payroll_payments', function (Blueprint $table): void {
                $table->dropColumn('net_amount');
            });
        }
        if (Schema::hasColumn('employee_payroll_payments', 'loan_deduction_amount')) {
            Schema::table('employee_payroll_payments', function (Blueprint $table): void {
                $table->dropColumn('loan_deduction_amount');
            });
        }

        if (Schema::hasColumn('finance_staff_advance_repayments', 'employee_payroll_payment_id')) {
            Schema::table('finance_staff_advance_repayments', function (Blueprint $table): void {
                $table->dropForeign('fin_stf_repay_payroll_fk');
                $table->dropColumn('employee_payroll_payment_id');
            });
        }

        foreach (['repayment_method', 'repayment_value'] as $column) {
            if (Schema::hasColumn('finance_staff_advances', $column)) {
                Schema::table('finance_staff_advances', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }

        foreach (['staff_repayment_method', 'staff_repayment_value'] as $column) {
            if (Schema::hasColumn('finance_expense_requests', $column)) {
                Schema::table('finance_expense_requests', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
