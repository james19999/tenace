<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_loyalty_settings')) {
            Schema::create('customer_loyalty_settings', function (Blueprint $table) {
                $table->id();
                $table->json('rules');
                $table->foreignId('updated_by')->nullable();
                $table->timestamps();
                $table->foreign('updated_by', 'cls_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('customer_loyalty_profiles')) {
            Schema::create('customer_loyalty_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('costumer_id')->unique();
                $table->unsignedTinyInteger('score')->default(0);
                $table->string('category', 32)->default('no_purchase');
                $table->json('metrics');
                $table->dateTime('calculated_at')->nullable();
                $table->timestamps();
                $table->foreign('costumer_id', 'clp_costumer_fk')->references('id')->on('costumers')->cascadeOnDelete();
                $table->index(['category', 'score'], 'clp_category_score_idx');
            });
        }

        if (! Schema::hasTable('customer_loyalty_score_histories')) {
            Schema::create('customer_loyalty_score_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('costumer_id');
                $table->unsignedTinyInteger('score');
                $table->string('category', 32);
                $table->json('metrics');
                $table->date('snapshot_date');
                $table->timestamps();
                $table->unique(['costumer_id', 'snapshot_date'], 'clsh_costumer_date_unique');
                $table->foreign('costumer_id', 'clsh_costumer_fk')->references('id')->on('costumers')->cascadeOnDelete();
                $table->index(['snapshot_date', 'category'], 'clsh_date_category_idx');
            });
        }

        if (! Schema::hasTable('customer_loyalty_referrals')) {
            Schema::create('customer_loyalty_referrals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('referrer_costumer_id');
                $table->unsignedBigInteger('referred_costumer_id');
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->date('referred_at')->nullable();
                $table->string('note', 500)->nullable();
                $table->timestamps();
                $table->unique('referred_costumer_id', 'clr_referred_unique');
                $table->foreign('referrer_costumer_id', 'clr_referrer_fk')->references('id')->on('costumers')->cascadeOnDelete();
                $table->foreign('referred_costumer_id', 'clr_referred_fk')->references('id')->on('costumers')->cascadeOnDelete();
                $table->foreign('recorded_by', 'clr_recorded_by_fk')->references('id')->on('users')->nullOnDelete();
                $table->index('referrer_costumer_id', 'clr_referrer_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_loyalty_referrals');
        Schema::dropIfExists('customer_loyalty_score_histories');
        Schema::dropIfExists('customer_loyalty_profiles');
        Schema::dropIfExists('customer_loyalty_settings');
    }
};
