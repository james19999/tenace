<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_theme_settings', function (Blueprint $table) {
            $table->string('appearance_mode', 8)->default('light')->after('corner_style');
        });
    }

    public function down(): void
    {
        Schema::table('app_theme_settings', function (Blueprint $table) {
            $table->dropColumn('appearance_mode');
        });
    }
};
