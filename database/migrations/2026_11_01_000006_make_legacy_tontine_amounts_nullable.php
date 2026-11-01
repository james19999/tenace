<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $changes = [];
            if (Schema::hasColumn('finance_tontines', 'weekly_contribution')) {
                $changes[] = 'MODIFY weekly_contribution BIGINT UNSIGNED NULL';
            }
            if (Schema::hasColumn('finance_tontines', 'expected_recovery_amount')) {
                $changes[] = 'MODIFY expected_recovery_amount BIGINT UNSIGNED NULL';
            }
            if ($changes !== []) {
                DB::statement('ALTER TABLE finance_tontines '.implode(', ', $changes));
            }

            return;
        }

        throw new RuntimeException('La migration des anciens montants de tontine est prévue pour MySQL.');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $changes = [];
            if (Schema::hasColumn('finance_tontines', 'weekly_contribution')) {
                DB::statement('UPDATE finance_tontines SET weekly_contribution = 0 WHERE weekly_contribution IS NULL');
                $changes[] = 'MODIFY weekly_contribution BIGINT UNSIGNED NOT NULL';
            }
            if (Schema::hasColumn('finance_tontines', 'expected_recovery_amount')) {
                DB::statement('UPDATE finance_tontines SET expected_recovery_amount = 0 WHERE expected_recovery_amount IS NULL');
                $changes[] = 'MODIFY expected_recovery_amount BIGINT UNSIGNED NOT NULL';
            }
            if ($changes !== []) {
                DB::statement('ALTER TABLE finance_tontines '.implode(', ', $changes));
            }
        }
    }
};
