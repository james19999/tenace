<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_general_cash_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cashier_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('finance_expense_requests', function (Blueprint $table): void {
            $table->json('receipt_paths')->nullable()->after('justification_note');
            $table->timestamp('receipts_submitted_at')->nullable()->after('receipt_paths');
        });
    }

    public function down(): void
    {
        Schema::table('finance_expense_requests', function (Blueprint $table): void {
            $table->dropColumn(['receipt_paths', 'receipts_submitted_at']);
        });

        Schema::dropIfExists('finance_general_cash_settings');
    }
};
