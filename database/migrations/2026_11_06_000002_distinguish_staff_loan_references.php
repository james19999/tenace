<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('finance_staff_advances')
            ->where('advance_type', 'loan')
            ->where('reference', 'like', 'AV-%')
            ->orderBy('id')
            ->get(['id', 'reference'])
            ->each(function (object $advance): void {
                $reference = preg_replace('/^AV-/', 'PRET-', $advance->reference);
                DB::table('finance_staff_advances')->where('id', $advance->id)->update(['reference' => $reference]);
            });
    }

    public function down(): void
    {
        DB::table('finance_staff_advances')
            ->where('advance_type', 'loan')
            ->where('reference', 'like', 'PRET-%')
            ->orderBy('id')
            ->get(['id', 'reference'])
            ->each(function (object $advance): void {
                $reference = preg_replace('/^PRET-/', 'AV-', $advance->reference);
                DB::table('finance_staff_advances')->where('id', $advance->id)->update(['reference' => $reference]);
            });
    }
};
