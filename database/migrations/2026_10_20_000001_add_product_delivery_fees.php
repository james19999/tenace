<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_country_prices', function (Blueprint $table): void {
            $table->unsignedBigInteger('delivery_fee')->default(0)->after('price');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('delivery_fee')->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('delivery_fee');
        });

        Schema::table('product_country_prices', function (Blueprint $table): void {
            $table->dropColumn('delivery_fee');
        });
    }
};
