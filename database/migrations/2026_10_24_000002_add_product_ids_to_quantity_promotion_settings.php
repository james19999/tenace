<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('quantity_promotion_settings', 'product_ids')) {
            Schema::table('quantity_promotion_settings', function (Blueprint $table): void {
                $table->json('product_ids')->nullable()->after('application_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('quantity_promotion_settings', 'product_ids')) {
            Schema::table('quantity_promotion_settings', function (Blueprint $table): void {
                $table->dropColumn('product_ids');
            });
        }
    }
};
