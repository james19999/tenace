<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_locations')) {
            Schema::create('stock_locations', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique('stloc_code_unique');
                $table->string('name', 120);
                $table->string('type', 32)->default('warehouse');
                $table->foreignId('responsible_user_id')->nullable();
                $table->foreignId('parent_location_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('responsible_user_id', 'stloc_user_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('parent_location_id', 'stloc_parent_fk')->references('id')->on('stock_locations')->nullOnDelete();
                $table->index(['type', 'is_active'], 'stloc_type_active_idx');
            });
        }

        if (! Schema::hasTable('stock_lots')) {
            Schema::create('stock_lots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id');
                $table->string('lot_code', 100)->nullable();
                $table->date('manufactured_at')->nullable();
                $table->date('expires_at')->nullable();
                $table->string('status', 24)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('product_id', 'stlot_product_fk')->references('id')->on('products')->restrictOnDelete();
                $table->unique(['product_id', 'lot_code'], 'stlot_product_code_unique');
                $table->index(['status', 'expires_at'], 'stlot_status_expiry_idx');
            });
        }

        if (! Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('transfer_number', 40)->unique('sttr_number_unique');
                $table->foreignId('source_location_id');
                $table->foreignId('destination_location_id');
                $table->string('status', 32)->default('draft');
                $table->dateTime('due_at')->nullable();
                $table->foreignId('requested_by')->nullable();
                $table->foreignId('authorized_by')->nullable();
                $table->foreignId('shipped_by')->nullable();
                $table->foreignId('received_by')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('authorized_at')->nullable();
                $table->dateTime('shipped_at')->nullable();
                $table->dateTime('received_at')->nullable();
                $table->dateTime('closed_at')->nullable();
                $table->text('reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('source_location_id', 'sttr_source_fk')->references('id')->on('stock_locations')->restrictOnDelete();
                $table->foreign('destination_location_id', 'sttr_dest_fk')->references('id')->on('stock_locations')->restrictOnDelete();
                $table->foreign('requested_by', 'sttr_requester_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('authorized_by', 'sttr_authorizer_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('shipped_by', 'sttr_shipper_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('received_by', 'sttr_receiver_fk')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'due_at'], 'sttr_status_due_idx');
                $table->index(['source_location_id', 'created_at'], 'sttr_source_created_idx');
                $table->index(['destination_location_id', 'created_at'], 'sttr_dest_created_idx');
            });
        }

        if (! Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->string('movement_number', 40)->unique('stmov_number_unique');
                $table->string('type', 32);
                $table->string('status', 24)->default('draft');
                $table->foreignId('transfer_id')->nullable();
                $table->foreignId('order_id')->nullable();
                $table->foreignId('reverses_movement_id')->nullable();
                $table->string('source_type', 40)->nullable();
                $table->string('source_reference', 100)->nullable();
                $table->dateTime('occurred_at');
                $table->string('origin', 24)->default('screen');
                $table->text('reason')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->foreignId('submitted_by')->nullable();
                $table->foreignId('approved_by')->nullable();
                $table->foreignId('posted_by')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->dateTime('posted_at')->nullable();
                $table->dateTime('reversed_at')->nullable();
                $table->timestamps();

                $table->foreign('transfer_id', 'stmov_transfer_fk')->references('id')->on('stock_transfers')->nullOnDelete();
                $table->foreign('order_id', 'stmov_order_fk')->references('id')->on('orders')->nullOnDelete();
                $table->foreign('reverses_movement_id', 'stmov_reverses_fk')->references('id')->on('stock_movements')->nullOnDelete();
                $table->foreign('created_by', 'stmov_creator_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('submitted_by', 'stmov_submitter_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('approved_by', 'stmov_approver_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('posted_by', 'stmov_poster_fk')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'occurred_at'], 'stmov_status_date_idx');
                $table->index(['type', 'occurred_at'], 'stmov_type_date_idx');
                $table->index(['source_type', 'source_reference'], 'stmov_source_ref_idx');
            });
        }

        if (! Schema::hasTable('stock_receipts')) {
            Schema::create('stock_receipts', function (Blueprint $table) {
                $table->id();
                $table->string('receipt_number', 40)->unique('strc_number_unique');
                $table->foreignId('location_id');
                $table->string('status', 24)->default('draft');
                $table->string('origin_name', 160)->nullable();
                $table->string('source_reference', 100)->nullable();
                $table->foreignId('movement_id')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->foreignId('validated_by')->nullable();
                $table->dateTime('occurred_at');
                $table->dateTime('validated_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('location_id', 'strc_location_fk')->references('id')->on('stock_locations')->restrictOnDelete();
                $table->foreign('movement_id', 'strc_movement_fk')->references('id')->on('stock_movements')->nullOnDelete();
                $table->foreign('created_by', 'strc_creator_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('validated_by', 'strc_validator_fk')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'occurred_at'], 'strc_status_date_idx');
            });
        }

        if (! Schema::hasTable('stock_receipt_lines')) {
            Schema::create('stock_receipt_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('receipt_id');
                $table->foreignId('product_id');
                $table->foreignId('lot_id')->nullable();
                $table->string('lot_code', 100)->nullable();
                $table->date('manufactured_at')->nullable();
                $table->date('expires_at')->nullable();
                $table->decimal('quantity_expected', 15, 3)->nullable();
                $table->decimal('quantity_received', 15, 3)->default(0);
                $table->decimal('quantity_damaged', 15, 3)->default(0);
                $table->decimal('unit_cost', 15, 2)->nullable();
                $table->text('discrepancy_note')->nullable();
                $table->timestamps();

                $table->foreign('receipt_id', 'strcl_receipt_fk')->references('id')->on('stock_receipts')->restrictOnDelete();
                $table->foreign('product_id', 'strcl_product_fk')->references('id')->on('products')->restrictOnDelete();
                $table->foreign('lot_id', 'strcl_lot_fk')->references('id')->on('stock_lots')->nullOnDelete();
                $table->index(['receipt_id', 'product_id'], 'strcl_receipt_product_idx');
            });
        }

        if (! Schema::hasTable('stock_receipt_events')) {
            Schema::create('stock_receipt_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_number', 48)->unique('strev_number_unique');
                $table->foreignId('receipt_id');
                $table->string('status', 24)->default('submitted');
                $table->foreignId('movement_id')->nullable();
                $table->foreignId('received_by')->nullable();
                $table->foreignId('approved_by')->nullable();
                $table->dateTime('occurred_at');
                $table->dateTime('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('receipt_id', 'strev_receipt_fk')->references('id')->on('stock_receipts')->restrictOnDelete();
                $table->foreign('movement_id', 'strev_movement_fk')->references('id')->on('stock_movements')->nullOnDelete();
                $table->foreign('received_by', 'strev_receiver_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('approved_by', 'strev_approver_fk')->references('id')->on('users')->nullOnDelete();
                $table->index(['receipt_id', 'status'], 'strev_receipt_status_idx');
            });
        }

        if (! Schema::hasTable('stock_receipt_event_lines')) {
            Schema::create('stock_receipt_event_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id');
                $table->foreignId('receipt_line_id');
                $table->foreignId('product_id');
                $table->foreignId('lot_id')->nullable();
                $table->decimal('quantity_received', 15, 3);
                $table->decimal('quantity_damaged', 15, 3)->default(0);
                $table->decimal('unit_cost', 15, 2)->nullable();
                $table->text('discrepancy_note')->nullable();
                $table->timestamps();

                $table->foreign('event_id', 'strevl_event_fk')->references('id')->on('stock_receipt_events')->restrictOnDelete();
                $table->foreign('receipt_line_id', 'strevl_receipt_line_fk')->references('id')->on('stock_receipt_lines')->restrictOnDelete();
                $table->foreign('product_id', 'strevl_product_fk')->references('id')->on('products')->restrictOnDelete();
                $table->foreign('lot_id', 'strevl_lot_fk')->references('id')->on('stock_lots')->nullOnDelete();
                $table->index(['event_id', 'product_id'], 'strevl_event_product_idx');
            });
        }

        if (! Schema::hasTable('stock_movement_lines')) {
            Schema::create('stock_movement_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('movement_id');
                $table->foreignId('product_id');
                $table->foreignId('location_id');
                $table->foreignId('lot_id')->nullable();
                $table->decimal('quantity_delta', 15, 3);
                $table->decimal('unit_cost', 15, 2)->nullable();
                $table->string('unit', 24)->default('piece');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('movement_id', 'stmline_movement_fk')->references('id')->on('stock_movements')->restrictOnDelete();
                $table->foreign('product_id', 'stmline_product_fk')->references('id')->on('products')->restrictOnDelete();
                $table->foreign('location_id', 'stmline_location_fk')->references('id')->on('stock_locations')->restrictOnDelete();
                $table->foreign('lot_id', 'stmline_lot_fk')->references('id')->on('stock_lots')->nullOnDelete();
                $table->index(['product_id', 'location_id', 'lot_id'], 'stmline_stock_key_idx');
            });
        }

        if (! Schema::hasTable('stock_transfer_lines')) {
            Schema::create('stock_transfer_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transfer_id');
                $table->foreignId('product_id');
                $table->foreignId('lot_id')->nullable();
                $table->decimal('quantity_requested', 15, 3);
                $table->decimal('quantity_shipped', 15, 3)->nullable();
                $table->decimal('quantity_received', 15, 3)->nullable();
                $table->decimal('quantity_damaged', 15, 3)->default(0);
                $table->text('discrepancy_note')->nullable();
                $table->timestamps();

                $table->foreign('transfer_id', 'sttl_transfer_fk')->references('id')->on('stock_transfers')->restrictOnDelete();
                $table->foreign('product_id', 'sttl_product_fk')->references('id')->on('products')->restrictOnDelete();
                $table->foreign('lot_id', 'sttl_lot_fk')->references('id')->on('stock_lots')->nullOnDelete();
                $table->index(['transfer_id', 'product_id'], 'sttl_transfer_product_idx');
            });
        }

        if (! Schema::hasTable('stock_transfer_events')) {
            Schema::create('stock_transfer_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_number', 48)->unique('sttev_number_unique');
                $table->foreignId('transfer_id');
                $table->string('type', 24);
                $table->foreignId('movement_id')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->dateTime('occurred_at');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('transfer_id', 'sttev_transfer_fk')->references('id')->on('stock_transfers')->restrictOnDelete();
                $table->foreign('movement_id', 'sttev_movement_fk')->references('id')->on('stock_movements')->nullOnDelete();
                $table->foreign('user_id', 'sttev_user_fk')->references('id')->on('users')->nullOnDelete();
                $table->index(['transfer_id', 'type', 'occurred_at'], 'sttev_transfer_type_date_idx');
            });
        }

        if (! Schema::hasTable('stock_transfer_event_lines')) {
            Schema::create('stock_transfer_event_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id');
                $table->foreignId('transfer_line_id');
                $table->foreignId('product_id');
                $table->foreignId('lot_id')->nullable();
                $table->decimal('quantity', 15, 3);
                $table->decimal('quantity_damaged', 15, 3)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('event_id', 'sttevl_event_fk')->references('id')->on('stock_transfer_events')->restrictOnDelete();
                $table->foreign('transfer_line_id', 'sttevl_transfer_line_fk')->references('id')->on('stock_transfer_lines')->restrictOnDelete();
                $table->foreign('product_id', 'sttevl_product_fk')->references('id')->on('products')->restrictOnDelete();
                $table->foreign('lot_id', 'sttevl_lot_fk')->references('id')->on('stock_lots')->nullOnDelete();
                $table->index(['event_id', 'product_id'], 'sttevl_event_product_idx');
            });
        }

        if (! Schema::hasTable('stock_counts')) {
            Schema::create('stock_counts', function (Blueprint $table) {
                $table->id();
                $table->string('count_number', 40)->unique('stcount_number_unique');
                $table->foreignId('location_id');
                $table->string('status', 24)->default('draft');
                $table->boolean('blind_count')->default(true);
                $table->boolean('is_opening_balance')->default(false);
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->foreignId('counter_id')->nullable();
                $table->foreignId('reviewed_by')->nullable();
                $table->foreignId('approved_by')->nullable();
                $table->text('scope')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('location_id', 'stcount_location_fk')->references('id')->on('stock_locations')->restrictOnDelete();
                $table->foreign('created_by', 'stcount_creator_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('counter_id', 'stcount_counter_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('reviewed_by', 'stcount_reviewer_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('approved_by', 'stcount_approver_fk')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'scheduled_at'], 'stcount_status_date_idx');
            });
        }

        if (! Schema::hasTable('stock_count_lines')) {
            Schema::create('stock_count_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('count_id');
                $table->foreignId('product_id');
                $table->foreignId('lot_id')->nullable();
                $table->decimal('expected_quantity', 15, 3)->nullable();
                $table->decimal('counted_quantity', 15, 3)->nullable();
                $table->decimal('second_count_quantity', 15, 3)->nullable();
                $table->string('condition', 24)->default('sellable');
                $table->text('discrepancy_note')->nullable();
                $table->timestamps();

                $table->foreign('count_id', 'stcline_count_fk')->references('id')->on('stock_counts')->restrictOnDelete();
                $table->foreign('product_id', 'stcline_product_fk')->references('id')->on('products')->restrictOnDelete();
                $table->foreign('lot_id', 'stcline_lot_fk')->references('id')->on('stock_lots')->nullOnDelete();
                $table->index(['count_id', 'product_id', 'lot_id'], 'stcline_count_product_idx');
            });
        }

        if (! Schema::hasTable('stock_adjustments')) {
            Schema::create('stock_adjustments', function (Blueprint $table) {
                $table->id();
                $table->string('adjustment_number', 40)->unique('stadj_number_unique');
                $table->string('status', 24)->default('draft');
                $table->string('reason_code', 40);
                $table->text('reason');
                $table->foreignId('count_id')->nullable();
                $table->foreignId('movement_id')->nullable();
                $table->foreignId('requested_by')->nullable();
                $table->foreignId('approved_by')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->dateTime('posted_at')->nullable();
                $table->timestamps();

                $table->foreign('count_id', 'stadj_count_fk')->references('id')->on('stock_counts')->nullOnDelete();
                $table->foreign('movement_id', 'stadj_movement_fk')->references('id')->on('stock_movements')->nullOnDelete();
                $table->foreign('requested_by', 'stadj_requester_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('approved_by', 'stadj_approver_fk')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'created_at'], 'stadj_status_created_idx');
            });
        }

        if (! Schema::hasTable('stock_adjustment_lines')) {
            Schema::create('stock_adjustment_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('adjustment_id');
                $table->foreignId('product_id');
                $table->foreignId('location_id');
                $table->foreignId('lot_id')->nullable();
                $table->decimal('quantity_before', 15, 3);
                $table->decimal('quantity_counted', 15, 3);
                $table->decimal('quantity_delta', 15, 3);
                $table->decimal('unit_cost', 15, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('adjustment_id', 'stadjl_adjustment_fk')->references('id')->on('stock_adjustments')->restrictOnDelete();
                $table->foreign('product_id', 'stadjl_product_fk')->references('id')->on('products')->restrictOnDelete();
                $table->foreign('location_id', 'stadjl_location_fk')->references('id')->on('stock_locations')->restrictOnDelete();
                $table->foreign('lot_id', 'stadjl_lot_fk')->references('id')->on('stock_lots')->nullOnDelete();
                $table->index(['location_id', 'product_id', 'lot_id'], 'stadjl_stock_key_idx');
            });
        }

        if (! Schema::hasTable('stock_module_settings')) {
            Schema::create('stock_module_settings', function (Blueprint $table) {
                $table->id();
                $table->json('rules');
                $table->foreignId('updated_by')->nullable();
                $table->timestamps();

                $table->foreign('updated_by', 'stset_user_fk')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('stock_audit_logs')) {
            Schema::create('stock_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->string('actor_role', 40)->nullable();
                $table->string('action', 64);
                $table->string('subject_type', 80);
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('before_values')->nullable();
                $table->json('after_values')->nullable();
                $table->text('reason')->nullable();
                $table->string('origin', 24)->default('screen');
                $table->string('result', 24)->default('success');
                $table->string('request_id', 64)->nullable();
                $table->timestamps();

                $table->foreign('user_id', 'staudit_user_fk')->references('id')->on('users')->nullOnDelete();
                $table->index(['subject_type', 'subject_id'], 'staudit_subject_idx');
                $table->index(['action', 'created_at'], 'staudit_action_date_idx');
                $table->index(['user_id', 'created_at'], 'staudit_user_date_idx');
            });
        }

        if (! Schema::hasTable('stock_alerts')) {
            Schema::create('stock_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('type', 40);
                $table->string('severity', 16)->default('warning');
                $table->string('status', 20)->default('open');
                $table->string('deduplication_key', 120)->nullable();
                $table->string('subject_type', 80)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->foreignId('location_id')->nullable();
                $table->foreignId('product_id')->nullable();
                $table->foreignId('assigned_to')->nullable();
                $table->string('title', 180);
                $table->text('message')->nullable();
                $table->json('details')->nullable();
                $table->dateTime('due_at')->nullable();
                $table->dateTime('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable();
                $table->timestamps();

                $table->foreign('location_id', 'stalert_location_fk')->references('id')->on('stock_locations')->nullOnDelete();
                $table->foreign('product_id', 'stalert_product_fk')->references('id')->on('products')->nullOnDelete();
                $table->foreign('assigned_to', 'stalert_assignee_fk')->references('id')->on('users')->nullOnDelete();
                $table->foreign('resolved_by', 'stalert_resolver_fk')->references('id')->on('users')->nullOnDelete();
                $table->index(['status', 'severity', 'due_at'], 'stalert_status_due_idx');
                $table->index(['subject_type', 'subject_id'], 'stalert_subject_idx');
                $table->index('deduplication_key', 'stalert_dedupe_idx');
            });
        }

        if (! Schema::hasTable('stock_attachments')) {
            Schema::create('stock_attachments', function (Blueprint $table) {
                $table->id();
                $table->string('subject_type', 80);
                $table->unsignedBigInteger('subject_id');
                $table->foreignId('uploaded_by')->nullable();
                $table->string('path', 500);
                $table->string('original_name', 255);
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->timestamps();

                $table->foreign('uploaded_by', 'statt_user_fk')->references('id')->on('users')->nullOnDelete();
                $table->index(['subject_type', 'subject_id'], 'statt_subject_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_attachments');
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('stock_audit_logs');
        Schema::dropIfExists('stock_module_settings');
        Schema::dropIfExists('stock_adjustment_lines');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_counts');
        Schema::dropIfExists('stock_transfer_event_lines');
        Schema::dropIfExists('stock_transfer_events');
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_movement_lines');
        Schema::dropIfExists('stock_receipt_event_lines');
        Schema::dropIfExists('stock_receipt_events');
        Schema::dropIfExists('stock_receipt_lines');
        Schema::dropIfExists('stock_receipts');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_lots');
        Schema::dropIfExists('stock_locations');
    }
};
