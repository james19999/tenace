<?php

namespace Tests\Unit;

use App\Models\OrderPayment;
use App\Models\Orders\Order;
use App\Services\Payments\FedaPayPaymentProcessor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class FedaPayPaymentProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('montant');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->string('partner_order_status')->nullable();
            $table->string('status')->nullable();
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
            $table->string('gateway_transaction_id')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('gateway_method')->nullable();
            $table->string('gateway_status')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('orders');
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_approved_transaction_confirms_once_and_updates_the_order_balance(): void
    {
        $order = Order::query()->create(['montant' => 4000, 'paid_amount' => 1000, 'partner_order_status' => 'out_for_delivery']);
        $payment = OrderPayment::query()->create([
            'order_id' => $order->id, 'stage' => 'on_delivery', 'method' => 'mobile_money',
            'amount' => 3000, 'status' => 'pending', 'gateway' => 'fedapay', 'gateway_transaction_id' => '123',
        ]);
        $transaction = ['id' => 123, 'amount' => 3000, 'status' => 'approved', 'currency' => ['iso' => 'XOF'], 'reference' => 'trx_123'];

        app(FedaPayPaymentProcessor::class)->refresh($payment, $transaction);
        app(FedaPayPaymentProcessor::class)->refresh($payment->fresh(), $transaction);

        $this->assertSame('confirmed', $payment->fresh()->status);
        $this->assertSame(4000, (int) $order->fresh()->paid_amount);
    }

    public function test_pending_transaction_does_not_mark_the_order_paid(): void
    {
        $order = Order::query()->create(['montant' => 4000, 'paid_amount' => 0, 'partner_order_status' => 'out_for_delivery']);
        $payment = OrderPayment::query()->create([
            'order_id' => $order->id, 'stage' => 'on_delivery', 'method' => 'mobile_money',
            'amount' => 4000, 'status' => 'pending', 'gateway' => 'fedapay', 'gateway_transaction_id' => '123',
        ]);

        app(FedaPayPaymentProcessor::class)->refresh($payment, [
            'id' => 123, 'amount' => 4000, 'status' => 'pending', 'currency' => ['iso' => 'XOF'],
        ]);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(0, (int) $order->fresh()->paid_amount);
    }

    public function test_transaction_with_wrong_amount_or_currency_is_rejected(): void
    {
        $order = Order::query()->create(['montant' => 4000, 'paid_amount' => 0]);
        $payment = OrderPayment::query()->create([
            'order_id' => $order->id, 'stage' => 'on_delivery', 'method' => 'mobile_money',
            'amount' => 4000, 'status' => 'pending', 'gateway' => 'fedapay', 'gateway_transaction_id' => '123',
        ]);
        $processor = app(FedaPayPaymentProcessor::class);

        try {
            $processor->refresh($payment, ['id' => 123, 'amount' => 3999, 'status' => 'approved', 'currency' => ['iso' => 'XOF']]);
            $this->fail('Mismatched amount was accepted.');
        } catch (RuntimeException) {
            $this->assertSame(0, (int) $order->fresh()->paid_amount);
        }

        $this->expectException(RuntimeException::class);
        $processor->refresh($payment, ['id' => 123, 'amount' => 4000, 'status' => 'approved', 'currency' => ['iso' => 'GNF']]);
    }

    public function test_refund_removes_unsettled_delivery_payment_from_order_balance(): void
    {
        $order = Order::query()->create(['montant' => 4000, 'paid_amount' => 4000, 'partner_order_status' => 'out_for_delivery']);
        $payment = OrderPayment::query()->create([
            'order_id' => $order->id, 'stage' => 'on_delivery', 'method' => 'mobile_money',
            'amount' => 4000, 'status' => 'confirmed', 'gateway' => 'fedapay', 'gateway_transaction_id' => '123',
        ]);

        app(FedaPayPaymentProcessor::class)->refresh($payment, [
            'id' => 123, 'amount' => 4000, 'status' => 'refunded', 'currency' => ['iso' => 'XOF'],
        ]);

        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame(0, (int) $order->fresh()->paid_amount);
    }
}
