<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('finance_equipment_purchase_requests', 'report_submitted_at')) {
            Schema::table('finance_equipment_purchase_requests', function (Blueprint $table): void {
                $table->timestamp('report_submitted_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('finance_equipment_purchase_requests', 'report_submitted_at')) {
            Schema::table('finance_equipment_purchase_requests', function (Blueprint $table): void {
                $table->dropColumn('report_submitted_at');
            });
        }
    }
};
