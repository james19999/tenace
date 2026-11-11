<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'delivery_waiting')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->boolean('delivery_waiting')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'delivery_waiting')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('delivery_waiting');
            });
        }
    }
};
