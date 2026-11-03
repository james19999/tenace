<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_expense_requests', function (Blueprint $table): void {
            $table->timestamp('receipt_received_at')->nullable()->after('receipts_submitted_at');
            $table->foreignId('receipt_received_by')->nullable()->after('receipt_received_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_expense_requests', function (Blueprint $table): void {
            $table->dropForeign(['receipt_received_by']);
            $table->dropColumn(['receipt_received_by', 'receipt_received_at']);
        });
    }
};
