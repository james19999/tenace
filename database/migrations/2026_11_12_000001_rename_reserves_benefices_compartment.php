<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('finance_cash_compartments')
            ->where('key', 'reserves_benefices')
            ->update([
                'name' => 'Réserves, tontine et régularisations de caisse',
                'description' => 'Réserve affectée aux cotisations tontine et aux régularisations de caisse approuvées.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('finance_cash_compartments')
            ->where('key', 'reserves_benefices')
            ->update([
                'name' => 'Réserves et bénéfices',
                'description' => 'Réserves et bénéfices approuvés.',
                'updated_at' => now(),
            ]);
    }
};
