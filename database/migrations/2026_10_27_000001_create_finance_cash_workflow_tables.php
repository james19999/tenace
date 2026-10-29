<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_cash_locks', function (Blueprint $table): void {
            $table->string('lock_key', 80)->primary();
            $table->timestamps();
        });

        Schema::create('finance_general_openings', function (Blueprint $table): void {
            $table->id();
            $table->string('opening_key', 32)->unique();
            $table->unsignedBigInteger('amount');
            $table->dateTime('occurred_at');
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('finance_cash_closures', function (Blueprint $table): void {
            $table->id();
            $table->date('closing_date')->unique();
            $table->unsignedBigInteger('theoretical_amount');
            $table->unsignedBigInteger('counted_amount');
            $table->bigInteger('variance_amount');
            $table->json('denominations')->nullable();
            $table->string('status', 24)->default('awaiting_review');
            $table->text('count_note')->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignId('counted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('counted_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'closing_date'], 'fin_cash_closure_status_date_idx');
        });

        Schema::create('finance_shop_openings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_location_id')->unique()->constrained('stock_locations')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->dateTime('occurred_at');
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('finance_cash_remittances', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('expected_amount')->default(0);
            $table->unsignedBigInteger('counted_amount');
            $table->unsignedBigInteger('handed_over_amount');
            $table->unsignedBigInteger('received_amount')->nullable();
            $table->string('destination', 24)->default('general_cash');
            $table->string('status', 32)->default('awaiting_collector');
            $table->foreignId('shop_manager_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('shop_manager_signed_at')->nullable();
            $table->foreignId('collector_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('collector_signed_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->string('bank_slip_reference', 120)->nullable();
            $table->string('bank_slip_path')->nullable();
            $table->string('signed_voucher_path')->nullable();
            $table->text('discrepancy_note')->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('paper_voucher_reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['stock_location_id', 'period_start', 'period_end'], 'fin_remit_location_period_idx');
            $table->index(['status', 'created_at'], 'fin_remit_status_created_idx');
        });

        Schema::create('finance_cash_movements', function (Blueprint $table): void {
            $table->id();
            $table->string('entry_number', 48)->unique();
            $table->string('movement_type', 32);
            $table->string('from_custody', 24);
            $table->string('to_custody', 24);
            $table->unsignedBigInteger('amount');
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->foreignId('remittance_id')->nullable()->constrained('finance_cash_remittances')->restrictOnDelete();
            $table->string('source_type', 64)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_key', 160)->nullable()->unique();
            $table->string('source_reference', 120)->nullable();
            $table->dateTime('occurred_at');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['stock_location_id', 'occurred_at'], 'fin_cash_location_date_idx');
            $table->index(['from_custody', 'to_custody', 'occurred_at'], 'fin_cash_custody_date_idx');
            $table->index(['source_type', 'source_id'], 'fin_cash_source_idx');
        });

        Schema::create('finance_expense_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('type_expensives')->nullOnDelete();
            $table->string('category_name', 120);
            $table->text('description');
            $table->unsignedBigInteger('amount');
            $table->string('beneficiary', 160);
            $table->string('budget_reference', 120)->nullable();
            $table->date('requested_payment_date')->nullable();
            $table->string('status', 32)->default('submitted');
            $table->string('approval_route', 32)->default('finance');
            $table->string('justification_path')->nullable();
            $table->text('justification_note')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('finance_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_reviewed_at')->nullable();
            $table->text('finance_comment')->nullable();
            $table->foreignId('director_decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('director_decided_at')->nullable();
            $table->text('director_comment')->nullable();
            $table->string('payment_status', 24)->default('unpaid');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'fin_exp_status_created_idx');
            $table->index(['stock_location_id', 'created_at'], 'fin_exp_location_created_idx');
            $table->index(['requested_by', 'created_at'], 'fin_exp_requester_created_idx');
        });

        Schema::create('finance_supplier_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('supplier_name', 160);
            $table->string('invoice_number', 120);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('amount');
            $table->foreignId('stock_receipt_id')->nullable()->constrained('stock_receipts')->restrictOnDelete();
            $table->string('status', 24)->default('awaiting_review');
            $table->string('payment_status', 24)->default('unpaid');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->string('invoice_path');
            $table->text('notes')->nullable();
            $table->text('review_comment')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['supplier_name', 'invoice_number'], 'fin_supplier_invoice_vendor_ref_unique');
            $table->index(['status', 'due_date'], 'fin_supplier_invoice_status_due_idx');
        });

        Schema::create('finance_supplier_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained('finance_supplier_invoices')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('payment_reference', 120)->nullable();
            $table->string('proof_path');
            $table->foreignId('paid_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at');
            $table->timestamps();
            $table->index(['supplier_invoice_id', 'paid_at'], 'fin_supplier_payment_invoice_date_idx');
        });

        Schema::create('finance_expense_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('expense_request_id')->constrained('finance_expense_requests')->restrictOnDelete();
            $table->string('stage', 24);
            $table->string('decision', 24);
            $table->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();
            $table->index(['expense_request_id', 'decided_at'], 'fin_exp_approval_request_date_idx');
        });

        Schema::create('finance_expense_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('expense_request_id')->constrained('finance_expense_requests')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('custody_account', 24);
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->string('payment_reference', 120)->nullable();
            $table->string('proof_path')->nullable();
            $table->foreignId('paid_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at');
            $table->timestamps();
            $table->index(['expense_request_id', 'paid_at'], 'fin_exp_payment_request_date_idx');
        });

        Schema::create('finance_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type', 80);
            $table->unsignedBigInteger('subject_id');
            $table->string('action', 64);
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id', 'created_at'], 'fin_audit_subject_date_idx');
            $table->index(['user_id', 'created_at'], 'fin_audit_user_date_idx');
        });

        Schema::create('finance_report_exports', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 48)->unique();
            $table->string('report_type', 32);
            $table->json('filters');
            $table->unsignedInteger('row_count');
            $table->unsignedBigInteger('amount_total')->default(0);
            $table->foreignId('exported_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('exported_at');
            $table->index(['report_type', 'exported_at'], 'fin_report_export_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_report_exports');
        Schema::dropIfExists('finance_audit_events');
        Schema::dropIfExists('finance_expense_payments');
        Schema::dropIfExists('finance_supplier_payments');
        Schema::dropIfExists('finance_supplier_invoices');
        Schema::dropIfExists('finance_expense_approvals');
        Schema::dropIfExists('finance_expense_requests');
        Schema::dropIfExists('finance_cash_movements');
        Schema::dropIfExists('finance_shop_openings');
        Schema::dropIfExists('finance_cash_closures');
        Schema::dropIfExists('finance_cash_remittances');
        Schema::dropIfExists('finance_cash_locks');
        Schema::dropIfExists('finance_general_openings');
    }
};
