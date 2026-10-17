<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('costumer_contact_settings', function (Blueprint $table) {
            $table->string('public_review_phone_mode', 24)->default('hidden');
        });
    }

    public function down(): void
    {
        Schema::table('costumer_contact_settings', function (Blueprint $table) {
            $table->dropColumn('public_review_phone_mode');
        });
    }
};
