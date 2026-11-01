<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE finance_tontines MODIFY weekly_contribution BIGINT UNSIGNED NULL, MODIFY expected_recovery_amount BIGINT UNSIGNED NULL');

            return;
        }

        throw new RuntimeException('La migration des anciens montants de tontine est prévue pour MySQL.');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('UPDATE finance_tontines SET weekly_contribution = 0 WHERE weekly_contribution IS NULL');
            DB::statement('UPDATE finance_tontines SET expected_recovery_amount = 0 WHERE expected_recovery_amount IS NULL');
            DB::statement('ALTER TABLE finance_tontines MODIFY weekly_contribution BIGINT UNSIGNED NOT NULL, MODIFY expected_recovery_amount BIGINT UNSIGNED NOT NULL');
        }
    }
};
