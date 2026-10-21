<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_appointment_availability_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dg_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
            $table->index(['dg_user_id', 'weekday'], 'dg_avail_ranges_user_day_idx');
        });

        if (Schema::hasTable('dg_appointment_availabilities')) {
            DB::table('dg_appointment_availabilities')->where('is_available', true)->orderBy('id')->get()->each(function ($range) {
                if ($range->starts_at && $range->ends_at) {
                    DB::table('dg_appointment_availability_ranges')->insert([
                        'dg_user_id' => $range->dg_user_id,
                        'weekday' => $range->weekday,
                        'starts_at' => $range->starts_at,
                        'ends_at' => $range->ends_at,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_appointment_availability_ranges');
    }
};
