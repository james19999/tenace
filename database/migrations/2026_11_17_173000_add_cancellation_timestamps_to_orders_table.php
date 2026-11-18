<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'canceled_at')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->timestamp('canceled_at')->nullable()->after('delivered_at');
                $table->boolean('canceled_at_is_estimated')->default(false)->after('canceled_at');
                $table->index(['status', 'canceled_at'], 'orders_status_canceled_at_idx');
            });
        }

        DB::table('orders')->where('status', 'canceled')->whereNull('canceled_at')->update([
            'canceled_at' => DB::raw('updated_at'),
            'canceled_at_is_estimated' => true,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'canceled_at')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropIndex('orders_status_canceled_at_idx');
                $table->dropColumn(['canceled_at', 'canceled_at_is_estimated']);
            });
        }
    }
};
