<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stock_receipts', 'expected_at')) {
            Schema::table('stock_receipts', function (Blueprint $table): void {
                $table->dateTime('expected_at')->nullable()->after('occurred_at');
            });
        }

        DB::table('stock_receipts')->whereNull('expected_at')->update(['expected_at' => DB::raw('occurred_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_receipts', 'expected_at')) {
            Schema::table('stock_receipts', function (Blueprint $table): void {
                $table->dropColumn('expected_at');
            });
        }
    }
};
