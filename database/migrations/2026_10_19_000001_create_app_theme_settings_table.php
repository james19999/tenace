<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_theme_settings', function (Blueprint $table) {
            $table->id();
            $table->char('primary_color', 7)->default('#7e1615');
            $table->char('secondary_color', 7)->default('#343a40');
            $table->char('accent_color', 7)->default('#5fb49c');
            $table->char('background_color', 7)->default('#f8f9fa');
            $table->char('surface_color', 7)->default('#ffffff');
            $table->char('text_color', 7)->default('#292d38');
            $table->string('corner_style', 16)->default('soft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_theme_settings');
    }
};
