<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_order_cancellation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->boolean('was_delivery_waiting')->default(false);
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'courier_cancel_status_created_idx');
            $table->index(['order_id', 'status'], 'courier_cancel_order_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_order_cancellation_requests');
    }
};
