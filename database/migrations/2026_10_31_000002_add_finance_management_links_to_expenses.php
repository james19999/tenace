<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_expense_requests', function (Blueprint $table): void {
            $table->foreignId('staff_user_id')->nullable()->after('requested_by')->constrained('users')->nullOnDelete();
            $table->foreignId('commission_user_id')->nullable()->after('staff_user_id')->constrained('users')->nullOnDelete();
            $table->date('commission_period_start')->nullable()->after('commission_user_id');
            $table->date('commission_period_end')->nullable()->after('commission_period_start');
        });
    }

    public function down(): void
    {
        Schema::table('finance_expense_requests', function (Blueprint $table): void {
            $table->dropForeign(['staff_user_id']);
            $table->dropForeign(['commission_user_id']);
            $table->dropColumn(['staff_user_id', 'commission_user_id', 'commission_period_start', 'commission_period_end']);
        });
    }
};
