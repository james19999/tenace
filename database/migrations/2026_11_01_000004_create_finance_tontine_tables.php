<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_tontines', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->string('name', 160);
            $table->date('first_contribution_on')->nullable();
            $table->date('next_contribution_on')->nullable();
            $table->date('expected_recovery_on')->nullable();
            $table->string('cash_custody', 24);
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->restrictOnDelete();
            $table->string('status', 24)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'next_contribution_on'], 'fin_tontine_status_next_idx');
        });

        Schema::create('finance_tontine_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tontine_id')->constrained('finance_tontines')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['tontine_id', 'user_id'], 'fin_tontine_assignee_unique');
            $table->index(['user_id', 'tontine_id'], 'fin_tontine_assignee_user_idx');
        });

        Schema::create('finance_tontine_movements', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 48)->unique();
            $table->uuid('submission_key')->unique();
            $table->foreignId('tontine_id')->constrained('finance_tontines')->restrictOnDelete();
            $table->string('type', 20);
            $table->date('due_on')->nullable();
            $table->unsignedBigInteger('amount');
            $table->dateTime('occurred_at');
            $table->string('proof_path')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('cash_movement_id')->nullable()->unique()->constrained('finance_cash_movements')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['tontine_id', 'type', 'occurred_at'], 'fin_tontine_move_type_date_idx');
            $table->index(['tontine_id', 'due_on'], 'fin_tontine_move_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_tontine_movements');
        Schema::dropIfExists('finance_tontine_assignees');
        Schema::dropIfExists('finance_tontines');
    }
};
