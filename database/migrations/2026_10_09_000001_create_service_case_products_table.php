<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_case_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();

            $table->foreign('case_id', 'scp_case_fk')
                ->references('id')->on('customer_service_cases')->cascadeOnDelete();
            $table->foreign('product_id', 'scp_product_fk')
                ->references('id')->on('products')->cascadeOnDelete();
            $table->unique(['case_id', 'product_id'], 'scp_case_product_unique');
        });

        DB::table('customer_service_cases')
            ->whereNotNull('product_id')
            ->orderBy('id')
            ->chunkById(500, function ($cases): void {
                $now = now();
                $rows = $cases->map(fn ($case) => [
                    'case_id' => $case->id,
                    'product_id' => $case->product_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($rows) {
                    DB::table('service_case_products')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_case_products');
    }
};
