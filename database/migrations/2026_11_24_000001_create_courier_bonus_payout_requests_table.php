<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_bonus_payout_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('week_id')->constrained('courier_bonus_weeks')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->string('status', 24)->default('requested');
            $table->timestamp('requested_at');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('manager_note')->nullable();
            $table->timestamps();
            $table->unique(['week_id', 'courier_id'], 'courier_bonus_payout_week_courier_unique');
            $table->index(['status', 'requested_at'], 'courier_bonus_payout_status_requested_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_bonus_payout_requests');
    }
};
