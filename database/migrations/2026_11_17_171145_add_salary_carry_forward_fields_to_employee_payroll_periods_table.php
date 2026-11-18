<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_payroll_periods', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_payroll_periods', 'carry_forward_in')) {
                $table->unsignedBigInteger('carry_forward_in')->nullable()->after('salary_amount');
            }

            if (! Schema::hasColumn('employee_payroll_periods', 'carried_forward_out')) {
                $table->unsignedBigInteger('carried_forward_out')->default(0)->after('carry_forward_in');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_payroll_periods', function (Blueprint $table): void {
            $columns = array_filter(['carry_forward_in', 'carried_forward_out'], fn (string $column): bool => Schema::hasColumn('employee_payroll_periods', $column));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
