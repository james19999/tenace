<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_report_exports')) {
            return;
        }

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
    }
};
