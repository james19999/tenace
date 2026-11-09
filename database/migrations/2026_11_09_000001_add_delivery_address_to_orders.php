<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'delivery_address')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->text('delivery_address')->nullable()->after('costumer_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'delivery_address')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('delivery_address');
            });
        }
    }
};
