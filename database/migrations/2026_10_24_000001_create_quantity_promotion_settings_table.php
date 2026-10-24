<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quantity_promotion_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->string('application_mode', 16)->default('global');
            $table->json('tiers')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quantity_promotion_settings');
    }
};
