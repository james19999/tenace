<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['costumer_id', 'id'], 'orders_costumer_id_id_follow_up_idx');
        });

        Schema::table('costumer_contact_histories', function (Blueprint $table) {
            $table->index(['costumer_id', 'contact_type'], 'cch_costumer_type_follow_up_idx');
            $table->index(['costumer_id', 'responded_at'], 'cch_costumer_responded_follow_up_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_costumer_id_id_follow_up_idx');
        });

        Schema::table('costumer_contact_histories', function (Blueprint $table) {
            $table->dropIndex('cch_costumer_type_follow_up_idx');
            $table->dropIndex('cch_costumer_responded_follow_up_idx');
        });
    }
};
