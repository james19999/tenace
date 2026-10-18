<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\StockLegacyWarehouseReconciliationService;
use Tests\TestCase;

class ProductionStockMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge();
        DB::setDefaultConnection('sqlite');
        $this->createMigrationSchema();
    }

    public function test_production_reassigns_legacy_balances_to_one_shop_and_empties_warehouses(): void
    {
        $warehouseId = $this->location('OLD-WH', 'Ancien entrepôt', 'warehouse');
        $shopId = $this->location('OLD-SHOP', 'Ancienne boutique', 'shop');
        $extraShopId = $this->location('OLD-SHOP-2', 'Boutique en trop', 'shop');
        $productId = DB::table('products')->insertGetId([
            'name' => 'Produit test', 'qt_initial' => 12, 'price_market' => 100,
        ]);

        $initialMovementId = DB::table('stock_movements')->insertGetId([
            'movement_number' => 'OLD-MVT', 'type' => 'opening_balance', 'status' => 'posted',
            'occurred_at' => now(), 'origin' => 'screen', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([[$warehouseId, 10], [$shopId, 4], [$extraShopId, 2]] as [$locationId, $quantity]) {
            DB::table('stock_movement_lines')->insert([
                'movement_id' => $initialMovementId, 'product_id' => $productId,
                'location_id' => $locationId, 'quantity_delta' => $quantity,
                'unit_cost' => 100, 'unit' => 'piece', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->runMigration('production');

        $canonicalWarehouse = DB::table('stock_locations')->where('code', 'ENTREPOT-TENACOS')->first();
        $canonicalShop = DB::table('stock_locations')->where('code', 'BOUTIQUE-TENACOS')->first();
        $this->assertSame('ENTREPOT TENACOS', $canonicalWarehouse->name);
        $this->assertSame('BOUTIQUE TENACOS', $canonicalShop->name);
        $this->assertSame(1, DB::table('stock_locations')->where('type', 'warehouse')->where('is_active', true)->count());
        $this->assertSame(1, DB::table('stock_locations')->where('type', 'shop')->where('is_active', true)->count());
        $this->assertSame(0, (int) DB::table('stock_movement_lines as line')
            ->join('stock_movements as movement', 'movement.id', '=', 'line.movement_id')
            ->join('stock_locations as location', 'location.id', '=', 'line.location_id')
            ->where('movement.status', 'posted')->where('location.type', 'warehouse')
            ->sum('line.quantity_delta'));
        $this->assertSame(12, (int) DB::table('stock_movement_lines as line')
            ->join('stock_movements as movement', 'movement.id', '=', 'line.movement_id')
            ->join('stock_locations as location', 'location.id', '=', 'line.location_id')
            ->where('movement.status', 'posted')->where('location.type', 'shop')
            ->sum('line.quantity_delta'));
        $rules = json_decode(DB::table('stock_module_settings')->value('rules'), true);
        $this->assertTrue($rules['legacy_catalogue_stock_migrated_to_shop']);
        $this->assertSame((int) $canonicalShop->id, (int) $rules['default_sales_location_id']);
        $this->assertSame(4, DB::table('stock_audit_logs')->where('action', 'inventory.production_catalogue_migrated')->count());

        $movementCount = DB::table('stock_movements')->count();
        $this->runMigration('production');
        $this->assertSame($movementCount, DB::table('stock_movements')->count(), 'Rerunning the conversion must not duplicate stock movements.');
    }

    public function test_migration_does_not_change_locations_outside_production(): void
    {
        $this->location('LOCAL-WH', 'Entrepôt local', 'warehouse');
        $this->runMigration('testing');

        $this->assertSame(1, DB::table('stock_locations')->count());
        $this->assertSame(0, DB::table('stock_module_settings')->count());
        $this->assertSame(0, DB::table('stock_movements')->count());
    }

    public function test_legacy_reconciliation_does_not_move_migrated_shop_stock_into_the_warehouse(): void
    {
        $this->location('ENTREPOT-TENACOS', 'ENTREPOT TENACOS', 'warehouse');
        DB::table('products')->insert(['name' => 'Produit test', 'qt_initial' => 12, 'price_market' => 100]);
        DB::table('stock_module_settings')->insert([
            'rules' => json_encode(['legacy_catalogue_stock_migrated_to_shop' => true]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $affectedProducts = app(StockLegacyWarehouseReconciliationService::class)->reconcileSingleWarehouse();

        $this->assertSame(0, $affectedProducts);
        $this->assertSame(0, DB::table('stock_movements')->count());
        $this->assertSame(0, DB::table('stock_movement_lines')->count());
    }

    private function runMigration(string $environment): void
    {
        $this->app['env'] = $environment;
        $migration = require database_path('migrations/2026_10_17_000002_reassign_production_catalogue_stock_to_shop.php');
        $migration->up();
        $this->app['env'] = 'testing';
    }

    private function location(string $code, string $name, string $type): int
    {
        return DB::table('stock_locations')->insertGetId([
            'code' => $code, 'name' => $name, 'type' => $type, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function createMigrationSchema(): void
    {
        Schema::create('stock_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('qt_initial')->nullable();
            $table->decimal('price_market', 15, 2)->nullable();
        });
        Schema::create('stock_module_settings', function (Blueprint $table): void {
            $table->id();
            $table->json('rules');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->string('movement_number');
            $table->string('type');
            $table->string('status');
            $table->string('source_type')->nullable();
            $table->string('source_reference')->nullable();
            $table->dateTime('occurred_at');
            $table->string('origin');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('posted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('stock_movement_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('movement_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity_delta', 15, 3);
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->string('unit')->default('piece');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('stock_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_role')->nullable();
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('origin');
            $table->string('result');
            $table->timestamps();
        });
    }
}
