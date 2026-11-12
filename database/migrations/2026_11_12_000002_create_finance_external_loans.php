<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_external_loans', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->string('lender_name', 160);
            $table->string('contract_reference', 100)->nullable();
            $table->unsignedBigInteger('received_amount');
            $table->unsignedBigInteger('total_repayable');
            $table->unsignedBigInteger('initial_repaid_amount')->default(0);
            $table->unsignedBigInteger('amount_repaid')->default(0);
            $table->date('received_on');
            $table->date('next_payment_on')->nullable();
            $table->unsignedBigInteger('installment_amount')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('cash_movement_id')->nullable()->unique()->constrained('finance_cash_movements')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'next_payment_on'], 'fin_ext_loan_status_due_idx');
        });

        Schema::create('finance_external_loan_repayments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_id')->constrained('finance_external_loans')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('paid_on');
            $table->date('next_payment_on')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('cash_movement_id')->nullable()->unique()->constrained('finance_cash_movements')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['loan_id', 'paid_on'], 'fin_ext_loan_repay_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_external_loan_repayments');
        Schema::dropIfExists('finance_external_loans');
    }
};
