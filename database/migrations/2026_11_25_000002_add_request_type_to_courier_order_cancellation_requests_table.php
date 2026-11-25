<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_order_cancellation_requests', function (Blueprint $table): void {
            $table->string('request_type', 20)->default('cancellation')->after('courier_id');
        });
    }

    public function down(): void
    {
        Schema::table('courier_order_cancellation_requests', function (Blueprint $table): void {
            $table->dropColumn('request_type');
        });
    }
};
