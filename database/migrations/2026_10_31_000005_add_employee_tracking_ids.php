<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table): void {
            $table->string('tracking_id', 24)->nullable()->after('user_id');
        });

        DB::table('users')->whereIn('user_type', [
            'ADMINUSER', 'CALLCENTER', 'VDS', 'MNG', 'SCR', 'LVS', 'CSA', 'COMPTABLE', 'RCM',
        ])->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                if (! DB::table('employee_profiles')->where('user_id', $user->id)->exists()) {
                    DB::table('employee_profiles')->insert([
                        'user_id' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        DB::table('employee_profiles')->whereNull('tracking_id')->orderBy('id')->chunkById(100, function ($profiles): void {
            foreach ($profiles as $profile) {
                do {
                    $trackingId = 'EMP-'.Str::upper(Str::random(12));
                } while (DB::table('employee_profiles')->where('tracking_id', $trackingId)->exists());

                DB::table('employee_profiles')->where('id', $profile->id)->update(['tracking_id' => $trackingId]);
            }
        });

        Schema::table('employee_profiles', function (Blueprint $table): void {
            $table->unique('tracking_id', 'employee_profiles_tracking_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table): void {
            $table->dropUnique('employee_profiles_tracking_id_unique');
            $table->dropColumn('tracking_id');
        });
    }
};
