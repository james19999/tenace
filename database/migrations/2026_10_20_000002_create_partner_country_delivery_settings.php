<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_country_delivery_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('country_code', 2)->unique();
            $table->string('delivery_mode', 20)->default('per_product');
            $table->unsignedBigInteger('order_delivery_fee')->default(0);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_fee_mode', 20)->nullable()->after('partner_order_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('delivery_fee_mode');
        });

        Schema::dropIfExists('partner_country_delivery_settings');
    }
};
