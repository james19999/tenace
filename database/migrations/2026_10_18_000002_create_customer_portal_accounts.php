<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_portal_accounts')) {
            Schema::create('customer_portal_accounts', function (Blueprint $table): void {
                $table->id();
                $table->string('phone_e164', 24)->unique();
                $table->string('password');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('customer_portal_account_costumer')) {
            Schema::create('customer_portal_account_costumer', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('customer_portal_account_id');
                $table->unsignedBigInteger('costumer_id');
                $table->timestamps();
                $table->unique(['customer_portal_account_id', 'costumer_id'], 'portal_account_costumer_unique');
                $table->foreign('customer_portal_account_id', 'cpa_account_fk')
                    ->references('id')->on('customer_portal_accounts')->cascadeOnDelete();
                $table->foreign('costumer_id', 'cpa_costumer_fk')
                    ->references('id')->on('costumers')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_portal_account_costumer');
        Schema::dropIfExists('customer_portal_accounts');
    }
};
