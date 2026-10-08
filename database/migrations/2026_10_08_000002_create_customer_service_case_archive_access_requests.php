<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_service_case_archive_access_requests')) {
            Schema::create('customer_service_case_archive_access_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('case_id');
                $table->unsignedBigInteger('requester_id');
                $table->timestamp('archive_snapshot_at');
                $table->string('status', 20)->default('pending');
                $table->text('request_note')->nullable();
                $table->unsignedBigInteger('decided_by')->nullable();
                $table->text('decision_note')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();
                $table->unique(['case_id', 'requester_id', 'archive_snapshot_at'], 'case_archive_access_cycle_unique');
                $table->index(['status', 'created_at'], 'case_archive_access_status_created_idx');
            });
        }

        // MySQL limits constraint names to 64 characters. Explicit short names
        // also let this migration continue after an earlier partial failure.
        $this->addForeignKeyIfMissing('case_id', 'customer_service_cases', 'csaca_case_fk', false);
        $this->addForeignKeyIfMissing('requester_id', 'users', 'csaca_requester_fk', false);
        $this->addForeignKeyIfMissing('decided_by', 'users', 'csaca_decider_fk', true);
    }

    private function addForeignKeyIfMissing(string $column, string $referencedTable, string $constraintName, bool $nullable): void
    {
        $existing = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
            ['customer_service_case_archive_access_requests', $column]
        );

        if ($existing) {
            return;
        }

        Schema::table('customer_service_case_archive_access_requests', function (Blueprint $table) use ($column, $referencedTable, $constraintName, $nullable) {
            $foreign = $table->foreign($column, $constraintName)->references('id')->on($referencedTable);
            if ($nullable) {
                $foreign->nullOnDelete();
            } else {
                $foreign->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_service_case_archive_access_requests');
    }
};
