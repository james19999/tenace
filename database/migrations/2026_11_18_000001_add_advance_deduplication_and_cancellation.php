<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_expense_requests', function (Blueprint $table): void {
            $table->string('submission_key', 36)->nullable()->unique();
        });

        Schema::table('finance_staff_advances', function (Blueprint $table): void {
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->foreignId('reversal_cash_movement_id')->nullable()->unique()
                ->constrained('finance_cash_movements')->restrictOnDelete();
            $table->index(['payroll_period_id', 'voided_at'], 'fin_adv_period_voided_idx');
        });
    }

    public function down(): void
    {
        Schema::table('finance_staff_advances', function (Blueprint $table): void {
            $table->dropIndex('fin_adv_period_voided_idx');
            $table->dropForeign(['voided_by']);
            $table->dropForeign(['reversal_cash_movement_id']);
            $table->dropUnique(['reversal_cash_movement_id']);
            $table->dropColumn(['voided_at', 'voided_by', 'void_reason', 'reversal_cash_movement_id']);
        });

        Schema::table('finance_expense_requests', function (Blueprint $table): void {
            $table->dropUnique(['submission_key']);
            $table->dropColumn('submission_key');
        });
    }
};
