<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCountryPrice;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerOrderingSetupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->default('secret');
            $table->string('user_type');
            $table->string('country_code', 2)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('price')->default(10000);
            $table->text('description')->nullable();
            $table->string('img')->nullable();
            $table->timestamps();
        });

        Schema::create('product_country_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('country_code', 2);
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('delivery_fee')->default(0);
            $table->boolean('has_delivery_fee_override')->default(false);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'country_code']);
        });

        Schema::create('product_country_price_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_country_price_id')->constrained('product_country_prices')->cascadeOnDelete();
            $table->unsignedInteger('min_quantity');
            $table->unsignedBigInteger('unit_price');
            $table->timestamps();
            $table->unique(['product_country_price_id', 'min_quantity']);
        });

        Schema::create('partner_country_delivery_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('country_code', 2)->unique();
            $table->string('delivery_mode')->default('per_order');
            $table->unsignedBigInteger('order_delivery_fee')->default(0);
            $table->timestamps();
        });
    }

    public function test_admin_can_save_prices_and_quantity_tiers_for_new_country_price(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'user_type' => User::ROLE_ADMIN,
        ]);

        $product = Product::query()->create([
            'name' => 'Test Product',
            'price' => 10000,
        ]);

        $this->actingAs($admin)
            ->put(route('partner-ordering.prices.save', ['country' => 'TG']), [
                'prices' => [
                    $product->id => 9000,
                ],
                'available' => [
                    $product->id => 1,
                ],
                'tier_configured' => [
                    $product->id => 1,
                ],
                'quantity_prices' => [
                    $product->id => [
                        [
                            'min_quantity' => 2,
                            'unit_price' => 8000,
                        ],
                        [
                            'min_quantity' => 5,
                            'unit_price' => 7000,
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        $countryPrice = ProductCountryPrice::query()
            ->where('product_id', $product->id)
            ->where('country_code', 'TG')
            ->first();

        $this->assertNotNull($countryPrice);
        $this->assertSame(9000, $countryPrice->price);
        $this->assertTrue($countryPrice->is_available);

        $tiers = $countryPrice->quantityTiers()->get();
        $this->assertCount(2, $tiers);
        $this->assertSame(2, $tiers[0]->min_quantity);
        $this->assertSame(8000, $tiers[0]->unit_price);
        $this->assertSame($countryPrice->id, $tiers[0]->product_country_price_id);
        $this->assertSame(5, $tiers[1]->min_quantity);
        $this->assertSame(7000, $tiers[1]->unit_price);
        $this->assertSame($countryPrice->id, $tiers[1]->product_country_price_id);
    }

    public function test_admin_can_update_existing_quantity_tiers(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User 2',
            'email' => 'admin2@test.com',
            'user_type' => User::ROLE_ADMIN,
        ]);

        $product = Product::query()->create([
            'name' => 'Test Product 2',
            'price' => 10000,
        ]);

        $countryPrice = ProductCountryPrice::query()->create([
            'product_id' => $product->id,
            'country_code' => 'TG',
            'price' => 10000,
            'is_available' => true,
        ]);

        $countryPrice->quantityTiers()->createMany([
            ['min_quantity' => 2, 'unit_price' => 9000],
        ]);

        $this->actingAs($admin)
            ->put(route('partner-ordering.prices.save', ['country' => 'TG']), [
                'prices' => [
                    $product->id => 9500,
                ],
                'available' => [
                    $product->id => 1,
                ],
                'tier_configured' => [
                    $product->id => 1,
                ],
                'quantity_prices' => [
                    $product->id => [
                        [
                            'min_quantity' => 3,
                            'unit_price' => 8500,
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        $tiers = $countryPrice->fresh()->quantityTiers()->get();
        $this->assertCount(1, $tiers);
        $this->assertSame(3, $tiers[0]->min_quantity);
        $this->assertSame(8500, $tiers[0]->unit_price);
    }
}
