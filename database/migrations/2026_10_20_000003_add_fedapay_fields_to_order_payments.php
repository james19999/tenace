<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table): void {
            $table->string('gateway', 24)->nullable()->after('method');
            $table->string('gateway_transaction_id', 80)->nullable()->unique();
            $table->string('gateway_reference', 160)->nullable();
            $table->string('gateway_method', 32)->nullable();
            $table->string('gateway_status', 40)->nullable();
            $table->index(['gateway', 'gateway_status']);
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table): void {
            $table->dropIndex(['gateway', 'gateway_status']);
            $table->dropUnique(['gateway_transaction_id']);
            $table->dropColumn([
                'gateway',
                'gateway_transaction_id',
                'gateway_reference',
                'gateway_method',
                'gateway_status',
            ]);
        });
    }
};
