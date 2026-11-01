<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'primary_stock_location_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('primary_stock_location_id')->nullable()->after('active')
                    ->constrained('stock_locations')->nullOnDelete();
            });
        }

        $defaultLocationId = DB::table('stock_locations')
            ->where('type', 'shop')->where('is_active', true)
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%grossiste%'])
            ->whereRaw('LOWER(code) NOT LIKE ?', ['%grossiste%'])
            ->orderBy('id')->value('id');

        if ($defaultLocationId) {
            DB::table('users')->whereIn('user_type', ['LVS', 'VDS', 'CSA', 'MNG', 'SCR', 'CALLCENTER', 'COMPTABLE', 'RCM'])
                ->whereNull('primary_stock_location_id')->update(['primary_stock_location_id' => $defaultLocationId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'primary_stock_location_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('primary_stock_location_id');
            });
        }
    }
};
