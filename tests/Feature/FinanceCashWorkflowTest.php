<?php

namespace Tests\Feature;

use App\Models\FinanceCashMovement;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceCashRemittance;
use App\Models\FinanceStaffAdvance;
use App\Models\FinanceCommissionSettlement;
use App\Models\Commission;
use App\Models\OrderPayment;
use App\Models\Orders\Order;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\FinanceCashLedgerService;
use App\Services\FinanceCashRemittanceService;
use App\Services\FinanceExpenseWorkflowService;
use App\Http\Controllers\Finance\FinanceManagementController;
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
        $migration = require database_path('migrations/2026_10_31_000001_create_finance_planning_tables.php');
        $migration->up();
        $migration = require database_path('migrations/2026_10_31_000002_add_finance_management_links_to_expenses.php');
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
        $requester = $this->user('Requester', User::ROLE_SECRETARY);
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

    public function test_staff_advance_is_tracked_only_after_the_approved_expense_is_paid(): void
    {
        $requester = $this->user('Advance requester', User::ROLE_SECRETARY);
        $employee = $this->user('Employee', User::ROLE_SALES);
        $accountant = $this->user('Finance reviewer', User::ROLE_ACCOUNTANT);
        $director = $this->user('Director', User::ROLE_ADMIN);
        $ledger = app(FinanceCashLedgerService::class);
        $ledger->initializeGeneralCash(100000, now()->subDay()->toDateTimeString(), $director, 'Solde de caisse de recette.');
        $workflow = app(FinanceExpenseWorkflowService::class);
        $expense = $workflow->submit([
            'category_name' => 'Avance au personnel', 'description' => 'Avance de recette au personnel.',
            'beneficiary' => $employee->name, 'amount' => 50001, 'justification_note' => 'Demande de recette.',
            'staff_user_id' => $employee->id,
        ], $requester);

        $workflow->financeDecision($expense, $accountant, 'approved', 'Contrôle effectué.');
        $this->assertSame('awaiting_director', $expense->fresh()->status);
        $this->assertSame(0, FinanceStaffAdvance::query()->count());

        $workflow->directorDecision($expense->fresh(), $director, 'approved', 'Autorisé.');
        $workflow->pay($expense->fresh(), $director, 50001, 'general_cash', null, 'TEST-AV-1', null);

        $this->assertSame(1, FinanceStaffAdvance::query()->count());
        $this->assertSame(50001, FinanceStaffAdvance::query()->firstOrFail()->amount);
        $this->assertSame(49999, $ledger->balance('general_cash'));

        Commission::query()->create(['user_id' => (string) $employee->id, 'amount' => 2000, 'total' => 2000, 'fixed' => 0]);
        $commissionExpense = $workflow->submit([
            'category_name' => 'Commission du personnel', 'description' => 'Commission calculée de recette.',
            'beneficiary' => $employee->name, 'amount' => 2000, 'justification_note' => 'Commission rapprochée des ventes.',
            'commission_user_id' => $employee->id, 'commission_period_start' => today()->startOfYear()->toDateString(),
            'commission_period_end' => today()->toDateString(),
        ], $requester);
        $workflow->financeDecision($commissionExpense, $accountant, 'approved', 'Montant vérifié.');
        $workflow->pay($commissionExpense->fresh(), $director, 2000, 'general_cash', null, 'TEST-COM-1', null);

        $this->assertSame(1, FinanceCommissionSettlement::query()->count());
        $this->assertSame(2000, FinanceCommissionSettlement::query()->firstOrFail()->amount);
        $this->assertSame(47999, $ledger->balance('general_cash'));
    }

    public function test_management_cash_to_collect_excludes_delivered_and_canceled_orders(): void
    {
        $accountant = $this->user('Accountant', User::ROLE_ACCOUNTANT);
        $customer = \App\Models\Costumer::query()->create(['name' => 'Client recette', 'phone' => '90000000']);
        $order = Order::query()->create([
            'user_id' => (string) $accountant->id, 'costumer_id' => (string) $customer->id,
            'subtotal' => '100000', 'tax' => '0', 'total' => '100000', 'montant' => '100000',
            'paid_amount' => 0, 'code' => 'REC-001', 'time' => now()->format('H:i'), 'status' => 'ordered',
            'date_order' => now()->subDays(12)->toDateString(),
        ]);
        $order->status = 'ordered';
        $order->save();
        $order->created_at = now()->subDays(12);
        $order->save();
        OrderPayment::query()->create([
            'order_id' => $order->id, 'stage' => 'order_validation', 'method' => 'cash',
            'amount' => 30000, 'status' => 'confirmed', 'collected_at' => now()->subDays(12),
        ]);
        $canceled = Order::query()->create([
            'user_id' => (string) $accountant->id, 'costumer_id' => (string) $customer->id,
            'subtotal' => '50000', 'tax' => '0', 'total' => '50000', 'montant' => '50000',
            'code' => 'REC-CANCELED', 'time' => now()->format('H:i'), 'status' => 'canceled',
        ]);
        $canceled->status = 'canceled';
        $canceled->save();
        $delivered = Order::query()->create([
            'user_id' => (string) $accountant->id, 'costumer_id' => (string) $customer->id,
            'subtotal' => '80000', 'tax' => '0', 'total' => '80000', 'montant' => '80000',
            'code' => 'REC-DELIVERED', 'time' => now()->format('H:i'), 'status' => 'delivered',
        ]);
        $delivered->status = 'delivered';
        $delivered->save();

        \App\Models\FinanceBudget::query()->create([
            'category_name' => 'Électricité', 'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(), 'amount' => 100000, 'created_by' => $accountant->id,
        ]);
        \App\Models\FinanceAsset::query()->create([
            'reference' => 'IMM-TEST-1', 'name' => 'Ordinateur', 'category' => 'Informatique', 'acquired_on' => now()->subMonths(18)->toDateString(),
            'purchase_amount' => 120000, 'residual_amount' => 12000, 'useful_life_months' => 36, 'status' => 'in_service', 'created_by' => $accountant->id,
        ]);
        $request = \Illuminate\Http\Request::create('/comptabilite/gestion', 'GET', ['tab' => 'receivables']);
        $request->setUserResolver(fn () => $accountant);
        $view = app(FinanceManagementController::class)->index($request, app(FinanceCashLedgerService::class));
        $receivables = $view->getData()['receivables'];

        $this->assertCount(1, $receivables);
        $this->assertSame('REC-001', $receivables->first()->code);
        $this->assertSame(70000, $receivables->first()->balance_due);
        $this->assertSame(12, $receivables->first()->balance_age_days);
        $this->assertSame(100000, $view->getData()['budgetTotal']);
        $this->assertSame(69000, $view->getData()['assets']->first()->estimated_book_value);
    }

    public function test_advanced_finance_management_is_restricted_to_admin_and_accountant(): void
    {
        $manager = $this->user('Shop manager finance access', User::ROLE_MANAGER);
        $request = \Illuminate\Http\Request::create('/comptabilite/gestion');
        $request->setUserResolver(fn () => $manager);

        try {
            app(FinanceManagementController::class)->index($request, app(FinanceCashLedgerService::class));
            $this->fail('Un responsable boutique ne doit pas accéder à la gestion financière avancée.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
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
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->string('type'); $table->morphs('notifiable');
            $table->text('data'); $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
        Schema::create('costumers', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('phone')->nullable(); $table->string('email')->nullable();
            $table->string('adresse')->nullable(); $table->unsignedBigInteger('user_id')->nullable(); $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id(); $table->string('user_id')->nullable(); $table->string('costumer_id'); $table->string('subtotal');
            $table->string('tax'); $table->string('total'); $table->string('montant')->nullable(); $table->unsignedBigInteger('paid_amount')->nullable();
            $table->string('code'); $table->string('time'); $table->string('status')->default('ordered'); $table->boolean('is_wholesale')->default(false);
            $table->unsignedBigInteger('partner_id')->nullable(); $table->unsignedBigInteger('public_order_link_id')->nullable();
            $table->timestamp('delivered_at')->nullable(); $table->date('date_order')->nullable(); $table->timestamps();
        });
        Schema::create('order_payments', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('order_id'); $table->string('stage'); $table->string('method');
            $table->unsignedBigInteger('amount'); $table->string('status'); $table->timestamp('collected_at')->nullable(); $table->timestamps();
        });
        Schema::create('commissions', function (Blueprint $table): void {
            $table->id(); $table->string('user_id'); $table->integer('amount'); $table->integer('total')->default(0);
            $table->integer('fixed')->default(0); $table->unsignedBigInteger('order_id')->nullable(); $table->timestamps();
        });
    }
}
