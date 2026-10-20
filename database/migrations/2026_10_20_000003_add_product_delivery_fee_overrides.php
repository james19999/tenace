<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_country_prices', function (Blueprint $table): void {
            $table->boolean('has_delivery_fee_override')->default(false)->after('delivery_fee');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->boolean('delivery_fee_is_override')->default(false)->after('delivery_fee');
        });

        DB::table('product_country_prices')->where('delivery_fee', '>', 0)->update(['has_delivery_fee_override' => true]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('delivery_fee_is_override');
        });

        Schema::table('product_country_prices', function (Blueprint $table): void {
            $table->dropColumn('has_delivery_fee_override');
        });
    }
};
