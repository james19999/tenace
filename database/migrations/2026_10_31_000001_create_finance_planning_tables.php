<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_budgets', function (Blueprint $table): void {
            $table->id();
            $table->string('category_name', 120);
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('amount');
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['period_start', 'period_end', 'stock_location_id'], 'fin_budget_period_location_idx');
        });

        Schema::create('finance_staff_advances', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 48)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('finance_expense_payment_id')->nullable()->unique()->constrained('finance_expense_payments')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('repaid_amount')->default(0);
            $table->date('granted_on');
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'granted_on'], 'fin_advance_user_date_idx');
        });

        Schema::create('finance_staff_advance_repayments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advance_id')->constrained('finance_staff_advances')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->dateTime('received_at');
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['advance_id', 'received_at'], 'fin_advance_repayment_date_idx');
        });

        Schema::create('finance_commission_settlements', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 48)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('period_start');
            $table->date('period_end');
            $table->dateTime('paid_at');
            $table->foreignId('paid_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'period_start', 'period_end'], 'fin_commission_user_period_idx');
        });

        Schema::create('finance_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 48)->unique();
            $table->string('name', 160);
            $table->string('category', 100);
            $table->date('acquired_on');
            $table->unsignedBigInteger('purchase_amount');
            $table->unsignedBigInteger('residual_amount')->default(0);
            $table->unsignedSmallInteger('useful_life_months');
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->string('supplier_name', 160)->nullable();
            $table->string('document_path')->nullable();
            $table->string('status', 24)->default('in_service');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'acquired_on'], 'fin_asset_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_assets');
        Schema::dropIfExists('finance_commission_settlements');
        Schema::dropIfExists('finance_staff_advance_repayments');
        Schema::dropIfExists('finance_staff_advances');
        Schema::dropIfExists('finance_budgets');
    }
};
