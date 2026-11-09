<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'delivery_phone')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->string('delivery_phone', 50)->nullable()->after('delivery_address');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'delivery_phone')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('delivery_phone');
            });
        }
    }
};
