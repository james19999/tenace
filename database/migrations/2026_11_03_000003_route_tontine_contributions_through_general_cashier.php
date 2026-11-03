<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_tontine_contribution_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 48)->unique();
            $table->unsignedBigInteger('tontine_id');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('amount');
            $table->text('note')->nullable();
            $table->string('status', 24)->default('awaiting_payment');
            $table->timestamp('requested_at');
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('cash_movement_id')->nullable()->unique();
            $table->unsignedBigInteger('tontine_movement_id')->nullable()->unique();
            $table->timestamps();

            $table->foreign('tontine_id', 'fin_ton_req_tontine_fk')->references('id')->on('finance_tontines')->cascadeOnDelete();
            $table->foreign('requested_by', 'fin_ton_req_requester_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('paid_by', 'fin_ton_req_payer_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cash_movement_id', 'fin_ton_req_cash_move_fk')->references('id')->on('finance_cash_movements')->nullOnDelete();
            $table->foreign('tontine_movement_id', 'fin_ton_req_ton_move_fk')->references('id')->on('finance_tontine_movements')->nullOnDelete();
            $table->index(['status', 'requested_at'], 'fin_ton_req_status_date_idx');
            $table->index(['tontine_id', 'status'], 'fin_ton_req_tontine_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_tontine_contribution_requests');
    }
};
