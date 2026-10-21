<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_appointment_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dg_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('dg_appointment_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dg_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->boolean('is_available')->default(false);
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->timestamps();
            $table->unique(['dg_user_id', 'weekday'], 'dg_avail_user_day_unique');
        });

        Schema::create('dg_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dg_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('visitor_name');
            $table->string('visitor_phone', 40);
            $table->string('subject', 500);
            $table->date('appointment_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
            $table->index(['dg_user_id', 'appointment_date', 'starts_at'], 'dg_appt_calendar_date_start_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_appointments');
        Schema::dropIfExists('dg_appointment_availabilities');
        Schema::dropIfExists('dg_appointment_calendars');
    }
};
