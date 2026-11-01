<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('finance_tontines')) {
            return;
        }

        $columns = ['first_contribution_on', 'next_contribution_on', 'expected_recovery_on'];
        if (DB::getDriverName() === 'mysql') {
            $changes = [];
            foreach ($columns as $column) {
                if (Schema::hasColumn('finance_tontines', $column)) {
                    $changes[] = "MODIFY {$column} DATE NULL";
                }
            }
            if ($changes !== []) {
                DB::statement('ALTER TABLE finance_tontines '.implode(', ', $changes));
            }

            return;
        }

        Schema::table('finance_tontines', function (Blueprint $table): void {
            foreach (['first_contribution_on', 'next_contribution_on', 'expected_recovery_on'] as $column) {
                if (Schema::hasColumn('finance_tontines', $column)) {
                    $table->date($column)->nullable()->change();
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('finance_tontines')) {
            return;
        }

        DB::table('finance_tontines')->whereNull('first_contribution_on')->update(['first_contribution_on' => DB::raw('CURRENT_DATE')]);
        DB::table('finance_tontines')->whereNull('next_contribution_on')->update(['next_contribution_on' => DB::raw('CURRENT_DATE')]);
        DB::table('finance_tontines')->whereNull('expected_recovery_on')->update(['expected_recovery_on' => DB::raw('CURRENT_DATE')]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE finance_tontines MODIFY first_contribution_on DATE NOT NULL, MODIFY next_contribution_on DATE NOT NULL, MODIFY expected_recovery_on DATE NOT NULL');
            return;
        }

        Schema::table('finance_tontines', function (Blueprint $table): void {
            $table->date('first_contribution_on')->nullable(false)->change();
            $table->date('next_contribution_on')->nullable(false)->change();
            $table->date('expected_recovery_on')->nullable(false)->change();
        });
    }
};
