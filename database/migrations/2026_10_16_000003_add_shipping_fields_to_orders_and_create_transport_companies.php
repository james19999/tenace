<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_companies', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_mode', 20)->default('local')->after('paid_amount');
            $table->foreignId('transport_company_id')->nullable()->after('delivery_mode')->constrained('transport_companies')->nullOnDelete();
            $table->string('shipping_country', 2)->nullable()->after('transport_company_id');
            $table->string('shipping_city', 120)->nullable()->after('shipping_country');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('transport_company_id');
            $table->dropColumn(['delivery_mode', 'shipping_country', 'shipping_city']);
        });

        Schema::dropIfExists('transport_companies');
    }
};
