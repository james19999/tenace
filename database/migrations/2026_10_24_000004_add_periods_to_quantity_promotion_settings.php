<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('quantity_promotion_settings', 'periods')) {
            Schema::table('quantity_promotion_settings', function (Blueprint $table): void {
                $table->json('periods')->nullable()->after('duration_days');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('quantity_promotion_settings', 'periods')) {
            Schema::table('quantity_promotion_settings', function (Blueprint $table): void {
                $table->dropColumn('periods');
            });
        }
    }
};
