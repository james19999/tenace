<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finance_equipment_purchase_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->string('title');
            $table->text('assignment_notes')->nullable();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('assigned')->index();
            $table->text('needed_items')->nullable();
            $table->unsignedBigInteger('requested_amount')->nullable();
            $table->unsignedBigInteger('approved_amount')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('cash_issued')->default(0);
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->unsignedBigInteger('spent_amount')->nullable();
            $table->unsignedBigInteger('returned_amount')->default(0);
            $table->text('report_note')->nullable();
            $table->json('receipt_paths')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('cash_movement_id')->nullable()->constrained('finance_cash_movements')->nullOnDelete();
            $table->foreignId('return_movement_id')->nullable()->constrained('finance_cash_movements')->nullOnDelete();
            $table->timestamps();
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_equipment_purchase_requests');
    }
};
