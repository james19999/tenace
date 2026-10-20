<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_country_price_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_country_price_id')->constrained('product_country_prices')->cascadeOnDelete();
            $table->unsignedInteger('min_quantity');
            $table->unsignedBigInteger('unit_price');
            $table->timestamps();
            $table->unique(['product_country_price_id', 'min_quantity'], 'pcpt_price_quantity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_country_price_tiers');
    }
};
