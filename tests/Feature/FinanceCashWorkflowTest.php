<?php

namespace Tests\Feature;

use App\Models\FinanceCashMovement;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceCashRemittance;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\FinanceCashLedgerService;
use App\Services\FinanceCashRemittanceService;
use App\Services\FinanceExpenseWorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinanceCashWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        $this->createBaseSchema();
        $migration = require database_path('migrations/2026_10_27_000001_create_finance_cash_workflow_tables.php');
        $migration->up();
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_cash_movements_are_idempotent_by_source_key(): void
    {
        $manager = $this->user('Manager', User::ROLE_MANAGER);
        $location = $this->shop($manager);
        $ledger = app(FinanceCashLedgerService::class);
        $attributes = [
            'movement_type' => 'sale_cash_received', 'from_custody' => 'external', 'to_custody' => 'shop',
            'amount' => 25000, 'stock_location_id' => $location->id, 'source_type' => 'test_payment',
            'source_id' => 1, 'source_key' => 'test-payment:1', 'source_reference' => 'TEST-1',
            'occurred_at' => now(), 'description' => 'Encaissement de test.', 'created_by' => $manager->id,
        ];

        $first = $ledger->record($attributes);
        $second = $ledger->record($attributes);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, FinanceCashMovement::query()->count());
        $this->assertSame(25000, $ledger->balance('shop', $location->id));
    }

    public function test_weekly_shop_remittance_moves_cash_through_transit_before_general_cash(): void
    {
        $manager = $this->user('Shop manager', User::ROLE_MANAGER);
        $collector = $this->user('Collector', User::ROLE_SECRETARY);
        $director = $this->user('Director', User::ROLE_ADMIN);
        $location = $this->shop($manager);
        $start = now()->startOfWeek()->subWeek();
        $end = $start->copy()->addDays(5);
        $ledger = app(FinanceCashLedgerService::class);
        $ledger->initializeShopCash($location->id, 100000, $start->copy()->addHours(9)->toDateTimeString(), $manager, 'Solde de test.');
        $service = app(FinanceCashRemittanceService::class);
        $remittance = $service->prepare([
            'stock_location_id' => $location->id,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'counted_amount' => 100000,
            'collector_id' => $collector->id,
            'destination' => 'general_cash',
        ], $manager);

        $this->assertSame(100000, $remittance->expected_amount);
        $service->collectorSign($remittance, $collector, 'signed-voucher-test.pdf');
        $this->assertSame(0, $ledger->balance('shop', $location->id));
        $this->assertSame(100000, $ledger->balance('transit', $location->id, $remittance->id));
        $this->assertSame(0, $ledger->balance('general_cash'));

        $service->confirmReceipt($remittance, $director, 100000);

        $this->assertSame('received', $remittance->fresh()->status);
        $this->assertSame(100000, $ledger->balance('general_cash'));
    }

    public function test_expense_threshold_requires_director_only_above_fifty_thousand(): void
    {
        $requester = $this->user('Requester', User::ROLE_MANAGER);
        $accountant = $this->user('Accountant', User::ROLE_ACCOUNTANT);
        $workflow = app(FinanceExpenseWorkflowService::class);
        $base = [
            'category_name' => 'Transport', 'description' => 'Frais de déplacement de test.',
            'beneficiary' => 'Prestataire test', 'justification_note' => 'Justificatif simulé pour recette.',
        ];
        $standard = $workflow->submit($base + ['amount' => 50000], $requester);
        $reinforced = $workflow->submit($base + ['amount' => 50001], $requester);

        $workflow->financeDecision($standard, $accountant, 'approved', 'Conforme au seuil standard.');
        $workflow->financeDecision($reinforced, $accountant, 'approved', 'À transmettre au DG.');

        $this->assertSame('authorized_to_pay', $standard->fresh()->status);
        $this->assertSame('awaiting_director', $reinforced->fresh()->status);
        $this->assertSame('finance', $standard->fresh()->approval_route);
        $this->assertSame('finance_and_director', $reinforced->fresh()->approval_route);
        $this->assertSame(2, FinanceExpenseRequest::query()->count());
    }

    private function user(string $name, string $role): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => str($name)->slug('-').'@test.local',
            'password' => 'test-password',
            'user_type' => $role,
            'active' => true,
        ]);
    }

    private function shop(User $manager): StockLocation
    {
        return StockLocation::query()->create([
            'code' => 'SHOP-TEST', 'name' => 'Boutique test', 'type' => 'shop',
            'responsible_user_id' => $manager->id, 'is_active' => true,
        ]);
    }

    private function createBaseSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email')->unique();
            $table->string('password')->nullable(); $table->string('user_type'); $table->boolean('active')->default(true);
            $table->rememberToken(); $table->timestamps();
        });
        Schema::create('stock_locations', function (Blueprint $table): void {
            $table->id(); $table->string('code')->unique(); $table->string('name'); $table->string('type');
            $table->unsignedBigInteger('responsible_user_id')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('type_expensives', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->timestamps();
        });
        Schema::create('stock_receipts', function (Blueprint $table): void {
            $table->id(); $table->string('receipt_number')->nullable(); $table->string('origin_name')->nullable();
            $table->unsignedBigInteger('location_id')->nullable(); $table->string('status')->nullable(); $table->timestamp('occurred_at')->nullable(); $table->timestamps();
        });
    }
}
