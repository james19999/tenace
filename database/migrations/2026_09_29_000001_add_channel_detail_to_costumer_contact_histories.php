<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('costumer_contact_histories', function (Blueprint $table) {
            $table->string('channel_detail', 120)->nullable()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('costumer_contact_histories', function (Blueprint $table) {
            $table->dropColumn('channel_detail');
        });
    }
};
