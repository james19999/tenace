<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('finance_equipment_purchase_requests', 'returned_received_at')) {
            Schema::table('finance_equipment_purchase_requests', function (Blueprint $table): void {
                $table->timestamp('returned_received_at')->nullable();
            });
        }
        if (! Schema::hasColumn('finance_equipment_purchase_requests', 'returned_received_by')) {
            Schema::table('finance_equipment_purchase_requests', function (Blueprint $table): void {
                $table->foreignId('returned_received_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('finance_equipment_purchase_requests', 'returned_received_by')) {
            Schema::table('finance_equipment_purchase_requests', function (Blueprint $table): void {
                $table->dropForeign(['returned_received_by']);
                $table->dropColumn('returned_received_by');
            });
        }
        if (Schema::hasColumn('finance_equipment_purchase_requests', 'returned_received_at')) {
            Schema::table('finance_equipment_purchase_requests', function (Blueprint $table): void {
                $table->dropColumn('returned_received_at');
            });
        }
    }
};
