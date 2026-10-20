<?php

namespace App\Services\Payments;

use App\Models\Orders\Order;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FedaPayService
{
    public function isConfigured(): bool
    {
        return config('services.fedapay.environment') === 'sandbox'
            && (string) config('services.fedapay.secret_key') !== ''
            && (string) config('services.fedapay.webhook_secret') !== '';
    }

    public function createTransaction(Order $order, string $phone, string $email): array
    {
        $customer = $order->costumer;
        $parts = preg_split('/\s+/', trim((string) ($customer?->name ?: 'Client')));
        $firstName = array_shift($parts) ?: 'Client';
        $lastName = implode(' ', $parts) ?: 'Tenace';

        $customerData = [
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $email,
            'phone_number' => ['number' => $phone, 'country' => 'tg'],
        ];

        $response = $this->request('post', '/transactions', [
            'description' => 'Commande '.$order->code.' - paiement à la livraison',
            'amount' => max(0, (int) $order->montant - (int) $order->paid_amount),
            'currency' => ['iso' => 'XOF'],
            'customer' => $customerData,
        ]);
        $transaction = $this->resource($response, 'transaction');
        if (empty($transaction['id'])) {
            throw new RuntimeException('FedaPay did not return a transaction identifier.');
        }

        return $transaction;
    }

    public function requestMobilePayment(string $transactionId, string $method): array
    {
        if (! in_array($method, ['togocel', 'moov_tg'], true)) {
            throw new RuntimeException('Unsupported Togo Mobile Money method.');
        }

        $tokenResponse = $this->request('post', '/transactions/'.rawurlencode($transactionId).'/token', []);
        $token = $this->resource($tokenResponse, 'token')['token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new RuntimeException('FedaPay did not return a payment token.');
        }

        return $this->resource($this->request('post', '/'.$method, ['token' => $token]), 'transaction');
    }

    public function retrieveTransaction(string $transactionId): array
    {
        return $this->resource(
            $this->request('get', '/transactions/'.rawurlencode($transactionId)),
            'transaction'
        );
    }

    public function verifyWebhookSignature(string $payload, ?string $header): bool
    {
        $secret = (string) config('services.fedapay.webhook_secret');
        if ($secret === '' || ! $header) {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $item) {
            [$key, $value] = array_pad(explode('=', trim($item), 2), 2, null);
            if ($key === 't' && is_numeric($value)) {
                $timestamp = (int) $value;
            } elseif ($key === 's' && is_string($value)) {
                $signatures[] = $value;
            }
        }
        if (! $timestamp || abs(time() - $timestamp) > 86400 || $signatures === []) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function request(string $method, string $path, array $payload = []): Response
    {
        if (config('services.fedapay.environment') !== 'sandbox') {
            throw new RuntimeException('This integration is restricted to the FedaPay Sandbox.');
        }
        $secret = (string) config('services.fedapay.secret_key');
        if ($secret === '') {
            throw new RuntimeException('FedaPay Sandbox is not configured.');
        }

        $request = Http::withToken($secret)->acceptJson()->asJson()->timeout(20);
        $url = rtrim((string) config('services.fedapay.base_url'), '/').$path;
        $response = $method === 'get' ? $request->get($url) : $request->post($url, $payload);
        $response->throw();

        return $response;
    }

    private function resource(Response $response, string $expected): array
    {
        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('FedaPay returned an invalid response.');
        }

        foreach (['v1/'.$expected, $expected, 'data'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        return $data;
    }
}
