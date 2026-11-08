<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_consignment_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->date('run_date');
            $table->foreignId('shop_location_id')->constrained('stock_locations')->restrictOnDelete();
            $table->foreignId('courier_location_id')->constrained('stock_locations')->restrictOnDelete();
            $table->foreignId('courier_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('cash_received')->default(0);
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['shop_location_id', 'run_date', 'status'], 'courier_run_shop_date_idx');
            $table->index(['courier_user_id', 'status'], 'courier_run_user_status_idx');
        });

        Schema::create('courier_consignment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained('courier_consignment_runs')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity_assigned', 15, 3)->default(0);
            $table->decimal('quantity_sold', 15, 3)->default(0);
            $table->decimal('quantity_returned', 15, 3)->default(0);
            $table->unsignedBigInteger('unit_price');
            $table->timestamps();
            $table->unique(['run_id', 'product_id'], 'courier_run_product_unique');
        });

        Schema::create('courier_consignment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained('courier_consignment_runs')->cascadeOnDelete();
            $table->string('event_type', 24);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('stock_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->foreignId('cash_movement_id')->nullable()->constrained('finance_cash_movements')->nullOnDelete();
            $table->unsignedBigInteger('amount')->default(0);
            $table->json('lines')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['run_id', 'event_type'], 'courier_event_run_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_consignment_events');
        Schema::dropIfExists('courier_consignment_lines');
        Schema::dropIfExists('courier_consignment_runs');
    }
};
