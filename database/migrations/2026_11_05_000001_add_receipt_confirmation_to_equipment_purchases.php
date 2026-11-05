<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('finance_equipment_purchase_requests', function (Blueprint $table): void {
            $table->timestamp('receipt_received_at')->nullable();
            $table->foreignId('receipt_received_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_equipment_purchase_requests', function (Blueprint $table): void {
            $table->dropForeign(['receipt_received_by']);
            $table->dropColumn(['receipt_received_at', 'receipt_received_by']);
        });
    }
};
