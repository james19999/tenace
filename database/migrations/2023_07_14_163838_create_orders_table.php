<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->string('costumer_id');
            $table->string('subtotal');
            $table->string('tax');
            $table->string('total');
            $table->string('motif')->nullable();
            $table->string('code');
            $table->string('time');
            $table->boolean('status_order')->default(false);
            $table->enum('status',['ordered','delivered','canceled'])->default('ordered');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
