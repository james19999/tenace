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
        Schema::create('pourcentage_commissions', function (Blueprint $table) {
            $table->id();
            $table->integer('amount')->default(0);
            $table->enum('percent',['Pub','Fond','Resp','Big','Im' ,'Epr'])->default('Im');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pourcentage_commissions');
    }
};
