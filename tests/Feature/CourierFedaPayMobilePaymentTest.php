<?php

namespace Tests\Feature;

use App\Models\OrderPayment;
use App\Models\Orders\Order;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourierFedaPayMobilePaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.fedapay.environment' => 'sandbox',
            'services.fedapay.secret_key' => 'sk_sandbox_fake',
            'services.fedapay.webhook_secret' => 'wh_sandbox_fake',
            'services.fedapay.base_url' => 'https://sandbox-api.fedapay.com/v1',
        ]);
        DB::purge('sqlite');

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('costumer_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('payment_timing')->nullable();
            $table->string('partner_order_status')->nullable();
            $table->string('status')->nullable();
            $table->string('code')->nullable();
            $table->unsignedBigInteger('montant');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
        Schema::create('costumers', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('adresse')->nullable();
            $table->timestamps();
        });
        Schema::create('order_courier_handoffs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('courier_id');
            $table->string('status');
            $table->unsignedBigInteger('received_by_courier')->nullable();
            $table->timestamps();
        });
        Schema::create('order_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('stage');
            $table->string('method');
            $table->unsignedBigInteger('amount');
            $table->string('status');
            $table->string('transaction_reference')->nullable();
            $table->string('gateway')->nullable();
            $table->string('gateway_transaction_id')->nullable()->unique();
            $table->string('gateway_reference')->nullable();
            $table->string('gateway_method')->nullable();
            $table->string('gateway_status')->nullable();
            $table->unsignedBigInteger('collected_by')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach (['order_payments', 'order_courier_handoffs', 'costumers', 'orders'] as $table) {
            Schema::dropIfExists($table);
        }
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_courier_can_start_sandbox_mixx_request_using_order_balance_not_browser_amount(): void
    {
        $courier = new User(['user_type' => User::ROLE_LIVREUR, 'country_code' => 'TG']);
        $courier->id = 41;
        $this->actingAs($courier);
        $order = $this->orderForCourier($courier, 5000, 1000);
        Http::fake([
            'https://sandbox-api.fedapay.com/v1/transactions' => Http::response([
                'v1/transaction' => ['id' => 123, 'reference' => 'trx_123', 'status' => 'pending'],
            ]),
            'https://sandbox-api.fedapay.com/v1/transactions/123/token' => Http::response(['token' => 'sandbox-token']),
            'https://sandbox-api.fedapay.com/v1/togocel' => Http::response(['v1/transaction' => ['id' => 123, 'status' => 'pending']]),
        ]);

        $response = $this->postJson(route('courier.public-orders.mobile-payment.start', $order), [
            'provider_method' => 'mixx',
            'phone' => '90 00 00 00',
            'email' => 'client@example.test',
            'amount' => 1,
            'currency' => 'GNF',
        ]);

        $response->assertAccepted()->assertJsonPath('status', 'pending');
        $payment = OrderPayment::query()->firstOrFail();
        $this->assertSame(4000, (int) $payment->amount);
        $this->assertSame('togocel', $payment->gateway_method);
        $this->assertSame('123', $payment->gateway_transaction_id);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/transactions')
            && $request['amount'] === 4000
            && $request['currency']['iso'] === 'XOF');
    }

    public function test_a_different_courier_cannot_start_a_payment_for_the_order(): void
    {
        $courier = new User(['user_type' => User::ROLE_LIVREUR, 'country_code' => 'TG']);
        $courier->id = 41;
        $this->actingAs($courier);
        $order = $this->orderForCourier($courier, 5000, 0, 99);
        Http::fake();

        $this->postJson(route('courier.public-orders.mobile-payment.start', $order), [
            'provider_method' => 'mixx', 'phone' => '90000000', 'email' => 'client@example.test',
        ])->assertConflict();

        Http::assertNothingSent();
        $this->assertSame(0, OrderPayment::query()->count());
    }

    public function test_repeated_start_requests_do_not_create_a_second_fedapay_transaction(): void
    {
        $courier = new User(['user_type' => User::ROLE_LIVREUR, 'country_code' => 'TG']);
        $courier->id = 41;
        $this->actingAs($courier);
        $order = $this->orderForCourier($courier, 5000, 0);
        Http::fake([
            'https://sandbox-api.fedapay.com/v1/transactions' => Http::response([
                'v1/transaction' => ['id' => 123, 'reference' => 'trx_123', 'status' => 'pending'],
            ]),
            'https://sandbox-api.fedapay.com/v1/transactions/123/token' => Http::response(['token' => 'sandbox-token']),
            'https://sandbox-api.fedapay.com/v1/togocel' => Http::response(['v1/transaction' => ['id' => 123, 'status' => 'pending']]),
        ]);
        $input = ['provider_method' => 'mixx', 'phone' => '90000000', 'email' => 'client@example.test'];

        $this->postJson(route('courier.public-orders.mobile-payment.start', $order), $input)->assertAccepted();
        $this->postJson(route('courier.public-orders.mobile-payment.start', $order), $input)->assertAccepted();

        $this->assertSame(1, OrderPayment::query()->count());
        Http::assertSentCount(3);
    }

    public function test_delivery_remains_blocked_until_fedapay_confirms_the_mobile_payment(): void
    {
        $courier = new User(['user_type' => User::ROLE_LIVREUR, 'country_code' => 'TG']);
        $courier->id = 41;
        $this->actingAs($courier);
        $order = $this->orderForCourier($courier, 5000, 0);

        $this->post(route('courier.public-orders.deliver', $order), ['payment_method' => 'mobile_money'])
            ->assertConflict();
        $this->assertSame('out_for_delivery', $order->fresh()->partner_order_status);
        $this->assertSame(0, OrderPayment::query()->count());
    }

    public function test_approved_sandbox_payment_can_then_complete_delivery(): void
    {
        $courier = new User(['user_type' => User::ROLE_LIVREUR, 'country_code' => 'TG']);
        $courier->id = 41;
        $this->actingAs($courier);
        $order = $this->orderForCourier($courier, 5000, 0);
        $payment = OrderPayment::query()->create([
            'order_id' => $order->id, 'stage' => 'on_delivery', 'method' => 'mobile_money',
            'amount' => 5000, 'status' => 'confirmed', 'gateway' => 'fedapay',
            'gateway_transaction_id' => 'trx-approved', 'gateway_method' => 'togocel', 'gateway_status' => 'approved',
        ]);
        $order->update(['paid_amount' => 5000]);

        $this->post(route('courier.public-orders.deliver', $order), ['payment_method' => 'mobile_money'])
            ->assertRedirect();

        $this->assertSame('delivered', $order->fresh()->partner_order_status);
        $this->assertSame('confirmed', $payment->fresh()->status);
    }

    public function test_webhook_with_invalid_signature_cannot_confirm_payment(): void
    {
        $order = $this->orderForCourier(new User(), 4000, 0);
        $payment = $this->pendingPayment($order, '123');
        Http::fake();
        $payload = json_encode(['name' => 'transaction.approved', 'entity' => ['id' => 123]], JSON_THROW_ON_ERROR);

        $this->call('POST', route('fedapay.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FEDAPAY_SIGNATURE' => 't='.time().',s=invalid',
        ], $payload)->assertStatus(400);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(0, (int) $order->fresh()->paid_amount);
        Http::assertNothingSent();
    }

    public function test_valid_repeated_webhook_confirms_the_matching_payment_only_once(): void
    {
        $order = $this->orderForCourier(new User(), 4000, 0);
        $payment = $this->pendingPayment($order, '123');
        Http::fake([
            'https://sandbox-api.fedapay.com/v1/transactions/123' => Http::response([
                'v1/transaction' => [
                    'id' => 123,
                    'amount' => 4000,
                    'status' => 'approved',
                    'currency' => ['iso' => 'XOF'],
                    'reference' => 'trx_123',
                ],
            ]),
        ]);
        $payload = json_encode(['name' => 'transaction.approved', 'entity' => ['id' => 123]], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'wh_sandbox_fake');
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_FEDAPAY_SIGNATURE' => 't='.$timestamp.',s='.$signature];

        $this->call('POST', route('fedapay.webhook'), [], [], [], $server, $payload)->assertOk();
        $this->call('POST', route('fedapay.webhook'), [], [], [], $server, $payload)->assertOk();

        $this->assertSame('confirmed', $payment->fresh()->status);
        $this->assertSame(4000, (int) $order->fresh()->paid_amount);
        Http::assertSentCount(2);
    }

    private function orderForCourier(User $courier, int $amount, int $paid, ?int $ownerId = null): Order
    {
        $courierId = $courier->id ?: 88;
        $customerId = DB::table('costumers')->insertGetId([
            'name' => 'Test Client', 'phone' => '90000000', 'email' => 'client@example.test',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $order = Order::query()->create([
            'costumer_id' => $customerId,
            'user_id' => $ownerId !== null ? (string) $ownerId : ($courier->id ? (string) $courier->id : null),
            'country_code' => 'TG',
            'payment_timing' => 'on_delivery',
            'partner_order_status' => 'out_for_delivery',
            'status' => 'ordered',
            'code' => 'WEB-TEST-1',
            'montant' => $amount,
            'paid_amount' => $paid,
        ]);
        DB::table('order_courier_handoffs')->insert([
            'order_id' => $order->id,
            'courier_id' => $courierId,
            'status' => 'received',
            'received_by_courier' => $courierId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $order;
    }

    private function pendingPayment(Order $order, string $transactionId): OrderPayment
    {
        return OrderPayment::query()->create([
            'order_id' => $order->id,
            'stage' => 'on_delivery',
            'method' => 'mobile_money',
            'amount' => (int) $order->montant,
            'status' => 'pending',
            'gateway' => 'fedapay',
            'gateway_transaction_id' => $transactionId,
            'gateway_method' => 'togocel',
            'gateway_status' => 'pending',
        ]);
    }
}
