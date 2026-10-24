<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quantity_promotion_settings')) return;

        if (! Schema::hasColumn('quantity_promotion_settings', 'application_mode')) {
            Schema::table('quantity_promotion_settings', function (Blueprint $table): void {
                $table->string('application_mode', 16)->default('general');
            });
        }
        if (! Schema::hasColumn('quantity_promotion_settings', 'product_ids')) {
            Schema::table('quantity_promotion_settings', function (Blueprint $table): void {
                $table->json('product_ids')->nullable();
            });
        }
        if (! Schema::hasColumn('quantity_promotion_settings', 'starts_at')) {
            Schema::table('quantity_promotion_settings', function (Blueprint $table): void {
                $table->dateTime('starts_at')->nullable();
            });
        }
        if (! Schema::hasColumn('quantity_promotion_settings', 'ends_at')) {
            Schema::table('quantity_promotion_settings', function (Blueprint $table): void {
                $table->dateTime('ends_at')->nullable();
            });
        }
        if (! Schema::hasColumn('quantity_promotion_settings', 'duration_days')) {
            Schema::table('quantity_promotion_settings', function (Blueprint $table): void {
                $table->unsignedSmallInteger('duration_days')->nullable();
            });
        }
        if (! Schema::hasColumn('quantity_promotion_settings', 'periods')) {
            Schema::table('quantity_promotion_settings', function (Blueprint $table): void {
                $table->json('periods')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Keep repaired columns in place: older migrations may already be recorded as run.
    }
};
