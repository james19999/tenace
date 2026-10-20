<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('partner_product_stocks')) {
            Schema::create('partner_product_stocks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('partner_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->char('country_code', 2);
                $table->unsignedInteger('quantity')->default(0);
                $table->timestamps();
                $table->unique(['partner_id', 'product_id'], 'partner_prod_partner_product_uq');
                $table->index(['country_code', 'quantity'], 'partner_prod_country_qty_idx');
            });
        }

        if (! Schema::hasTable('partner_courier_stock_issues')) {
            Schema::create('partner_courier_stock_issues', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('partner_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('courier_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
                $table->char('country_code', 2);
                $table->timestamp('issued_at');
                $table->string('notes', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('partner_courier_stock_issue_lines')) {
            Schema::create('partner_courier_stock_issue_lines', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('issue_id')->constrained('partner_courier_stock_issues')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
                $table->unsignedInteger('quantity_issued');
                $table->unsignedInteger('quantity_remaining');
                $table->timestamps();
                $table->index(['product_id', 'quantity_remaining'], 'courier_issue_product_remaining_idx');
            });
        }

        if (Schema::hasTable('stock_locations') && ! Schema::hasColumn('stock_locations', 'country_code')) {
            Schema::table('stock_locations', function (Blueprint $table): void {
                $table->char('country_code', 2)->nullable()->index('stloc_country_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_courier_stock_issue_lines');
        Schema::dropIfExists('partner_courier_stock_issues');
        Schema::dropIfExists('partner_product_stocks');
        if (Schema::hasTable('stock_locations') && Schema::hasColumn('stock_locations', 'country_code')) {
            Schema::table('stock_locations', function (Blueprint $table): void {
                $table->dropIndex('stloc_country_idx');
                $table->dropColumn('country_code');
            });
        }
    }
};
