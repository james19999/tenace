<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('country_code', 2)->nullable()->index();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 32)->nullable();
        });

        Schema::create('courier_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('document_type', 40)->default('identity_card');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'document_type']);
        });

        Schema::create('product_country_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('country_code', 2);
            $table->unsignedBigInteger('price');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'country_code']);
            $table->index(['country_code', 'is_available']);
        });

        Schema::create('public_order_links', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('title', 160)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_active', 'expires_at']);
        });

        Schema::create('public_order_link_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('public_order_link_id')->constrained('public_order_links')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['public_order_link_id', 'product_id'], 'public_link_product_unique');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('country_code', 2)->nullable()->index();
            $table->foreignId('partner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('public_order_link_id')->nullable()->constrained('public_order_links')->nullOnDelete();
            $table->string('payment_timing', 32)->nullable();
            $table->string('requested_payment_method', 32)->nullable();
            $table->uuid('public_tracking_token')->nullable()->unique();
            $table->string('partner_order_status', 40)->nullable()->index();
        });

        Schema::create('order_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('stage', 32);
            $table->string('method', 32);
            $table->unsignedBigInteger('amount');
            $table->string('status', 40)->default('awaiting_partner_confirmation');
            $table->string('transaction_reference', 160)->nullable();
            $table->string('proof_path')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->index(['stage', 'status']);
        });

        Schema::create('order_courier_handoffs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by_courier')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('prepared');
            $table->timestamp('handed_over_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->index(['partner_id', 'created_at']);
            $table->index(['courier_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_courier_handoffs');
        Schema::dropIfExists('order_payments');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('public_order_link_id');
            $table->dropConstrainedForeignId('partner_id');
            $table->dropColumn(['country_code', 'payment_timing', 'requested_payment_method', 'public_tracking_token', 'partner_order_status']);
        });

        Schema::dropIfExists('public_order_link_products');
        Schema::dropIfExists('public_order_links');
        Schema::dropIfExists('product_country_prices');
        Schema::dropIfExists('courier_documents');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['country_code', 'emergency_contact_name', 'emergency_contact_phone']);
        });
    }
};
