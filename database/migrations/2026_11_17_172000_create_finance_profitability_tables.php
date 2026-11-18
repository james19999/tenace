<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_profitability_periods', function (Blueprint $table): void {
            $table->id();
            $table->date('period_start')->unique();
            $table->date('period_end');
            $table->string('status', 16)->default('open');
            $table->json('snapshot')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_profitability_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->date('period_start')->index();
            $table->string('kind', 32);
            $table->string('nature', 16)->default('variable');
            $table->unsignedBigInteger('amount');
            $table->string('description', 500);
            $table->string('status', 16)->default('active');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();
            $table->index(['period_start', 'status', 'kind'], 'fin_profit_adjust_period_status_kind_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_profitability_adjustments');
        Schema::dropIfExists('finance_profitability_periods');
    }
};
