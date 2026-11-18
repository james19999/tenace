<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_director_wallets')
            && ! Schema::hasColumn('finance_director_wallets', 'remuneration_compartment_synced_balance')) {
            Schema::table('finance_director_wallets', function (Blueprint $table): void {
                $table->unsignedBigInteger('remuneration_compartment_synced_balance')->default(0)->after('started_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('finance_director_wallets', 'remuneration_compartment_synced_balance')) {
            Schema::table('finance_director_wallets', function (Blueprint $table): void {
                $table->dropColumn('remuneration_compartment_synced_balance');
            });
        }
    }
};
