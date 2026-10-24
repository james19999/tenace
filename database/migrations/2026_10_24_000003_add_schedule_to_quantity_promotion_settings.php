<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quantity_promotion_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('quantity_promotion_settings', 'starts_at')) {
                $table->dateTime('starts_at')->nullable();
            }
            if (! Schema::hasColumn('quantity_promotion_settings', 'ends_at')) {
                $table->dateTime('ends_at')->nullable();
            }
            if (! Schema::hasColumn('quantity_promotion_settings', 'duration_days')) {
                $table->unsignedSmallInteger('duration_days')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quantity_promotion_settings', function (Blueprint $table): void {
            $columns = array_filter(['starts_at', 'ends_at', 'duration_days'], fn ($column) => Schema::hasColumn('quantity_promotion_settings', $column));
            if ($columns) $table->dropColumn($columns);
        });
    }
};
