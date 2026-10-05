<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addIndexIfNotExists('costumers', 'costumers_created_at_index', ['created_at']);
        $this->addIndexIfNotExists('orders', 'orders_created_at_index', ['created_at']);
        $this->addIndexIfNotExists('orders', 'orders_status_created_at_index', ['status', 'created_at']);
        $this->addIndexIfNotExists('orders', 'orders_brouillon_created_at_index', ['brouillon', 'created_at']);
        $this->addIndexIfNotExists('expensives', 'expensives_created_at_index', ['created_at']);
        $this->addIndexIfNotExists('pubs', 'pubs_created_at_index', ['created_at']);
        $this->addIndexIfNotExists('imprevus', 'imprevus_created_at_index', ['created_at']);
        $this->addIndexIfNotExists('fonds', 'fonds_created_at_index', ['created_at']);
        $this->addIndexIfNotExists('epargnes', 'epargnes_created_at_index', ['created_at']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('costumers', 'costumers_created_at_index');
        $this->dropIndexIfExists('orders', 'orders_created_at_index');
        $this->dropIndexIfExists('orders', 'orders_status_created_at_index');
        $this->dropIndexIfExists('orders', 'orders_brouillon_created_at_index');
        $this->dropIndexIfExists('expensives', 'expensives_created_at_index');
        $this->dropIndexIfExists('pubs', 'pubs_created_at_index');
        $this->dropIndexIfExists('imprevus', 'imprevus_created_at_index');
        $this->dropIndexIfExists('fonds', 'fonds_created_at_index');
        $this->dropIndexIfExists('epargnes', 'epargnes_created_at_index');
    }

    private function addIndexIfNotExists(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $existing = collect(DB::select("SHOW INDEX FROM {$table}"))->pluck('Key_name')->unique();
        if (!$existing->contains($indexName)) {
            Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                $t->index($columns, $indexName);
            });
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $existing = collect(DB::select("SHOW INDEX FROM {$table}"))->pluck('Key_name')->unique();
        if ($existing->contains($indexName)) {
            Schema::table($table, function (Blueprint $t) use ($indexName) {
                $t->dropIndex($indexName);
            });
        }
    }
};
