<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('finance_cash_compartments')) {
            Schema::create('finance_cash_compartments', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 48)->unique();
                $table->string('name', 120);
                $table->string('description', 255);
                $table->unsignedTinyInteger('percentage')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('finance_cash_compartment_entries')) {
            Schema::create('finance_cash_compartment_entries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('compartment_id')->constrained('finance_cash_compartments')->restrictOnDelete();
                $table->foreignId('cash_movement_id')->nullable()->constrained('finance_cash_movements')->nullOnDelete();
                $table->string('entry_type', 24);
                $table->unsignedBigInteger('amount');
                $table->unsignedTinyInteger('percentage_snapshot')->nullable();
                $table->string('source_key', 140)->unique();
                $table->string('description', 255)->nullable();
                $table->timestamp('occurred_at');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['compartment_id', 'occurred_at'], 'fcc_entries_comp_date_idx');
            });
        }

        // MySQL may have created the table before failing on the original long index name.
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'finance_cash_compartment_entries')
            ->where('index_name', 'fcc_entries_comp_date_idx')
            ->exists();
        if (! $indexExists) {
            Schema::table('finance_cash_compartment_entries', function (Blueprint $table): void {
                $table->index(['compartment_id', 'occurred_at'], 'fcc_entries_comp_date_idx');
            });
        }

        if (! Schema::hasColumn('finance_expense_requests', 'compartment_id')) {
            Schema::table('finance_expense_requests', function (Blueprint $table): void {
                $table->foreignId('compartment_id')->nullable()->after('category_id')
                    ->constrained('finance_cash_compartments')->nullOnDelete();
            });
        }

        $now = now();
        DB::table('finance_cash_compartments')->insertOrIgnore([
            ['key' => 'dg_remuneration', 'name' => 'Rémunération du DG', 'description' => 'Salaire, indemnités et avantages du Directeur général.', 'percentage' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'approvisionnement', 'name' => 'Approvisionnement', 'description' => 'Achats de marchandises et réapprovisionnement du stock.', 'percentage' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'exploitation', 'name' => 'Fonds d’exploitation', 'description' => 'Salaires, loyers et dépenses courantes.', 'percentage' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'investissements', 'name' => 'Investissements', 'description' => 'Matériel, outils et infrastructures.', 'percentage' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'fiscalite', 'name' => 'Fiscalité', 'description' => 'Impôts, taxes et obligations fiscales.', 'percentage' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'securite', 'name' => 'Trésorerie de sécurité', 'description' => 'Réserve disponible pour les imprévus.', 'percentage' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'developpement', 'name' => 'Développement', 'description' => 'Expansion, marketing et nouveaux marchés.', 'percentage' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'reserves_benefices', 'name' => 'Réserves, tontine et régularisations de caisse', 'description' => 'Réserve affectée aux cotisations tontine et aux régularisations de caisse approuvées.', 'percentage' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('finance_expense_requests')
            ->whereNull('compartment_id')
            ->whereIn('status', ['awaiting_finance', 'awaiting_director', 'authorized_to_pay', 'partially_paid'])
            ->update(['compartment_id' => DB::table('finance_cash_compartments')->where('key', 'exploitation')->value('id')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('finance_expense_requests', 'compartment_id')) {
            Schema::table('finance_expense_requests', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('compartment_id');
            });
        }
        Schema::dropIfExists('finance_cash_compartment_entries');
        Schema::dropIfExists('finance_cash_compartments');
    }
};
