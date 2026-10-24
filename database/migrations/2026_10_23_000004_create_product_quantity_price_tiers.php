<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_quantity_price_tiers')) {
            Schema::create('product_quantity_price_tiers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('min_quantity');
                $table->unsignedBigInteger('unit_price');
                $table->timestamps();
                $table->unique(['product_id', 'min_quantity'], 'pqty_tier_product_quantity_unique');
            });
        }

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'is_wholesale')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->boolean('is_wholesale')->default(false)->after('type');
            });
            if (Schema::hasTable('users')) {
                DB::table('orders')->whereIn('created_user', DB::table('users')->select('id')->where('user_type', 'GRO'))
                    ->update(['is_wholesale' => true]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'is_wholesale')) {
            Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('is_wholesale'));
        }
        Schema::dropIfExists('product_quantity_price_tiers');
    }
};
