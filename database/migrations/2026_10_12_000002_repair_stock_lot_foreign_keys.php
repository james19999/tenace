<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $database = DB::connection()->getDatabaseName();
        $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->whereIn('CONSTRAINT_NAME', ['strcl_lot_fk', 'strevl_lot_fk'])
            ->pluck('CONSTRAINT_NAME')
            ->all();

        if (Schema::hasTable('stock_receipt_lines') && ! in_array('strcl_lot_fk', $constraints, true)) {
            Schema::table('stock_receipt_lines', function (Blueprint $table): void {
                $table->foreign('lot_id', 'strcl_lot_fk')->references('id')->on('stock_lots')->nullOnDelete();
            });
        }

        if (Schema::hasTable('stock_receipt_event_lines') && ! in_array('strevl_lot_fk', $constraints, true)) {
            Schema::table('stock_receipt_event_lines', function (Blueprint $table): void {
                $table->foreign('lot_id', 'strevl_lot_fk')->references('id')->on('stock_lots')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_receipt_event_lines')) {
            Schema::table('stock_receipt_event_lines', function (Blueprint $table): void {
                $table->dropForeign('strevl_lot_fk');
            });
        }
        if (Schema::hasTable('stock_receipt_lines')) {
            Schema::table('stock_receipt_lines', function (Blueprint $table): void {
                $table->dropForeign('strcl_lot_fk');
            });
        }
    }
};
