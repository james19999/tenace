<?php

namespace App\Http\Controllers;

use App\Models\OrderPayment;
use App\Services\Payments\FedaPayPaymentProcessor;
use App\Services\Payments\FedaPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FedaPayWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        FedaPayService $fedapay,
        FedaPayPaymentProcessor $processor
    ): JsonResponse {
        $payload = $request->getContent();
        if (! $fedapay->verifyWebhookSignature($payload, $request->header('X-FEDAPAY-SIGNATURE'))) {
            Log::notice('Rejected a FedaPay webhook with an invalid signature.');

            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $event = json_decode($payload, true);
        if (! is_array($event)) {
            return response()->json(['message' => 'Invalid event.'], 400);
        }

        $eventName = (string) ($event['name'] ?? $event['type'] ?? '');
        if (! str_starts_with($eventName, 'transaction.')) {
            return response()->json(['received' => true]);
        }

        $entity = $event['entity'] ?? $event['data'] ?? null;
        if (is_string($entity)) {
            $entity = json_decode($entity, true);
        }
        if (! is_array($entity)) {
            $entity = [];
        }
        $transactionId = (string) ($entity['id'] ?? data_get($entity, 'transaction.id') ?? '');
        if ($transactionId === '') {
            return response()->json(['message' => 'Transaction identifier is missing.'], 400);
        }

        $payment = OrderPayment::query()
            ->where('gateway', 'fedapay')
            ->where('gateway_transaction_id', $transactionId)
            ->first();
        if (! $payment) {
            // A webhook can arrive between FedaPay transaction creation and the
            // local ID save. A later delivery or the courier status check will reconcile it.
            Log::info('FedaPay webhook received for an unmatched transaction.', [
                'gateway_transaction_id' => $transactionId,
                'event' => $eventName,
            ]);

            return response()->json(['received' => true]);
        }

        try {
            $transaction = $fedapay->retrieveTransaction($transactionId);
            $processor->refresh($payment, $transaction);
        } catch (\Throwable $exception) {
            Log::warning('FedaPay webhook could not reconcile the transaction.', [
                'payment_id' => $payment->id,
                'gateway_transaction_id' => $transactionId,
                'event' => $eventName,
                'exception' => get_class($exception),
            ]);

            return response()->json(['message' => 'Transaction verification failed.'], 500);
        }

        return response()->json(['received' => true]);
    }
}
