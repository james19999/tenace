<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX costumers_name_lookup_idx ON costumers (name(100))');
        DB::statement('CREATE INDEX costumers_phone_lookup_idx ON costumers (phone(32))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX costumers_name_lookup_idx ON costumers');
        DB::statement('DROP INDEX costumers_phone_lookup_idx ON costumers');
    }
};
