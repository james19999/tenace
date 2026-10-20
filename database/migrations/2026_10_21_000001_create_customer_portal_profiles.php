<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_portal_profiles')) {
            Schema::create('customer_portal_profiles', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('customer_portal_account_id');
                $table->unsignedBigInteger('source_costumer_id')->nullable();
                $table->string('name', 160);
                $table->string('phone', 50)->nullable();
                $table->string('email', 160)->nullable();
                $table->string('address', 180)->nullable();
                $table->timestamps();
                $table->foreign('customer_portal_account_id', 'cpp_account_fk')
                    ->references('id')->on('customer_portal_accounts')->cascadeOnDelete();
                $table->foreign('source_costumer_id', 'cpp_costumer_fk')
                    ->references('id')->on('costumers')->nullOnDelete();
                $table->unique(['customer_portal_account_id', 'source_costumer_id'], 'cpp_account_source_unique');
                $table->index(['customer_portal_account_id', 'name'], 'cpp_account_name_idx');
            });
        }

        if (Schema::hasTable('customer_portal_account_costumer') && Schema::hasTable('costumers')) {
            DB::table('customer_portal_account_costumer as account_customer')
                ->join('costumers as customer', 'customer.id', '=', 'account_customer.costumer_id')
                ->selectRaw('account_customer.customer_portal_account_id, MIN(customer.id) as source_costumer_id, MAX(customer.name) as name, MAX(customer.phone) as phone, MAX(customer.email) as email, MAX(customer.adresse) as address')
                ->groupBy('account_customer.customer_portal_account_id', 'customer.name', 'customer.phone', 'customer.email')
                ->orderBy('account_customer.customer_portal_account_id')
                ->orderBy('source_costumer_id')
                ->chunk(500, function ($customers): void {
                    $now = now();
                    DB::table('customer_portal_profiles')->insertOrIgnore($customers->map(fn ($customer) => [
                        'customer_portal_account_id' => $customer->customer_portal_account_id,
                        'source_costumer_id' => $customer->source_costumer_id,
                        'name' => $customer->name ?: 'Client',
                        'phone' => $customer->phone,
                        'email' => $customer->email,
                        'address' => $customer->address,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all());
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_portal_profiles');
    }
};
