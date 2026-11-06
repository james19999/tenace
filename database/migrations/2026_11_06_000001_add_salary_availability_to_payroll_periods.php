<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employee_payroll_periods', 'available_at')) {
            Schema::table('employee_payroll_periods', function (Blueprint $table): void {
                $table->dateTime('available_at')->nullable();
            });
        }

        if (! Schema::hasColumn('employee_payroll_periods', 'available_by')) {
            Schema::table('employee_payroll_periods', function (Blueprint $table): void {
                $table->foreignId('available_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        // Les périodes déjà suivies restent visibles aux salariés.
        DB::table('employee_payroll_periods')->whereNull('available_at')->update(['available_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('employee_payroll_periods', 'available_by')) {
            Schema::table('employee_payroll_periods', function (Blueprint $table): void {
                $table->dropForeign(['available_by']);
                $table->dropColumn('available_by');
            });
        }

        if (Schema::hasColumn('employee_payroll_periods', 'available_at')) {
            Schema::table('employee_payroll_periods', fn (Blueprint $table) => $table->dropColumn('available_at'));
        }
    }
};
