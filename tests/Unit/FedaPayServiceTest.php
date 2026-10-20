<?php

namespace Tests\Unit;

use App\Models\Costumer;
use App\Models\Orders\Order;
use App\Services\Payments\FedaPayService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FedaPayServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.fedapay.environment' => 'sandbox',
            'services.fedapay.secret_key' => 'sk_sandbox_fake',
            'services.fedapay.webhook_secret' => 'wh_sandbox_fake',
            'services.fedapay.base_url' => 'https://sandbox-api.fedapay.com/v1',
        ]);
    }

    public function test_it_creates_an_integer_xof_transaction_for_the_server_calculated_balance(): void
    {
        Http::fake([
            'https://sandbox-api.fedapay.com/v1/transactions' => Http::response([
                'v1/transaction' => ['id' => 123, 'reference' => 'trx_sandbox_123', 'status' => 'pending'],
            ]),
        ]);

        $order = new Order(['code' => 'WEB-TEST', 'montant' => 4500, 'paid_amount' => 1200]);
        $order->setRelation('costumer', new Costumer(['name' => 'Amina Doe']));

        $transaction = app(FedaPayService::class)->createTransaction($order, '90000000', 'client@example.test');

        $this->assertSame(123, $transaction['id']);
        Http::assertSent(function (Request $request): bool {
            return str_ends_with($request->url(), '/transactions')
                && $request['amount'] === 3300
                && $request['currency']['iso'] === 'XOF'
                && $request['customer']['phone_number']['number'] === '90000000'
                && $request['customer']['phone_number']['country'] === 'tg'
                && $request['customer']['email'] === 'client@example.test';
        });
    }

    public function test_it_sends_a_mixx_mobile_money_request_using_fedapay_togocel_mode(): void
    {
        Http::fake([
            'https://sandbox-api.fedapay.com/v1/transactions/123/token' => Http::response(['token' => 'sandbox-token']),
            'https://sandbox-api.fedapay.com/v1/togocel' => Http::response(['v1/transaction' => ['id' => 123, 'status' => 'pending']]),
        ]);

        app(FedaPayService::class)->requestMobilePayment('123', 'togocel');

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/togocel')
            && $request['token'] === 'sandbox-token');
    }

    public function test_live_environment_is_refused_even_if_configured(): void
    {
        config(['services.fedapay.environment' => 'live']);

        $this->expectException(\RuntimeException::class);
        app(FedaPayService::class)->retrieveTransaction('123');
    }

    public function test_it_verifies_the_fedapay_timestamped_signature(): void
    {
        $payload = '{"name":"transaction.approved"}';
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'wh_sandbox_fake');

        $this->assertTrue(app(FedaPayService::class)->verifyWebhookSignature($payload, 't='.$timestamp.',s='.$signature));
        $this->assertFalse(app(FedaPayService::class)->verifyWebhookSignature($payload, 't='.$timestamp.',s=invalid'));
    }
}
