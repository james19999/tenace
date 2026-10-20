<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_order_link_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('public_order_link_id')->constrained('public_order_links')->cascadeOnDelete();
            $table->char('visitor_hash', 64);
            $table->unsignedInteger('visits')->default(0);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['public_order_link_id', 'visitor_hash'], 'polv_link_visitor_unique');
            $table->index(['public_order_link_id', 'last_seen_at'], 'polv_link_last_seen_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_order_link_visits');
    }
};
