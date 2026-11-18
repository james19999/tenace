<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_director_wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamps();
        });

        Schema::create('finance_director_wallet_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained('finance_director_wallets')->cascadeOnDelete();
            $table->string('name', 100);
            $table->unsignedTinyInteger('percentage')->default(0);
            $table->timestamps();
            $table->unique(['wallet_id', 'name']);
        });

        Schema::create('finance_director_wallet_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained('finance_director_wallets')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_director_wallet_categories')->nullOnDelete();
            $table->string('entry_type', 24);
            $table->unsignedBigInteger('amount');
            $table->string('source_key', 160)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['wallet_id', 'occurred_at']);
            $table->index(['category_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_director_wallet_entries');
        Schema::dropIfExists('finance_director_wallet_categories');
        Schema::dropIfExists('finance_director_wallets');
    }
};
