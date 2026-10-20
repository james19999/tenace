<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_order_links', function (Blueprint $table): void {
            $table->string('country_code', 2)->nullable()->unique();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_location_url', 2048)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('delivery_location_url');
        });

        Schema::table('public_order_links', function (Blueprint $table): void {
            $table->dropUnique(['country_code']);
            $table->dropColumn('country_code');
        });
    }
};
