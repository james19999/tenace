<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('courier_bonus_payout_requests', 'stock_location_id')) {
            Schema::table('courier_bonus_payout_requests', function (Blueprint $table): void {
                $table->foreignId('stock_location_id')->nullable()->after('courier_id')
                    ->constrained('stock_locations')->nullOnDelete();
            });
        }

        DB::table('courier_bonus_payout_requests as payouts')
            ->join('users', 'users.id', '=', 'payouts.courier_id')
            ->whereNull('payouts.stock_location_id')
            ->whereNotNull('users.primary_stock_location_id')
            ->update(['payouts.stock_location_id' => DB::raw('users.primary_stock_location_id')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('courier_bonus_payout_requests', 'stock_location_id')) {
            Schema::table('courier_bonus_payout_requests', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('stock_location_id');
            });
        }
    }
};
