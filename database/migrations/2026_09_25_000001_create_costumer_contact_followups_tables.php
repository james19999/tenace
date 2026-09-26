<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costumer_contact_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costumer_id')->constrained('costumers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('contact_type', ['initial', 'follow_up'])->default('initial');
            $table->enum('channel', ['whatsapp', 'sms', 'call', 'other']);
            $table->dateTime('contacted_at');
            $table->dateTime('follow_up_at')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->text('response')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['costumer_id', 'contacted_at']);
            $table->index('follow_up_at');
        });

        Schema::create('costumer_contact_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('default_follow_up_days')->default(14);
            $table->string('default_country_calling_code', 8)->default('+228');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costumer_contact_settings');
        Schema::dropIfExists('costumer_contact_histories');
    }
};
