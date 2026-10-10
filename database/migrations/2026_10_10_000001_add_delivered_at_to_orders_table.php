<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'delivered_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('delivered_at')->nullable()->after('assigned_at');
                $table->index(['status', 'delivered_at'], 'orders_status_delivered_at_idx');
            });
        }

        // Existing delivered orders only have updated_at as the closest available
        // record of when the delivery was validated.
        DB::table('orders')
            ->where('status', 'delivered')
            ->whereNull('delivered_at')
            ->update(['delivered_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'delivered_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_status_delivered_at_idx');
                $table->dropColumn('delivered_at');
            });
        }
    }
};
