<?php

namespace App\Http\Controllers;

use App\Models\OrderCourierHandoff;
use App\Models\OrderPayment;
use App\Models\Orders\Order;
use App\Models\User;
use App\Services\Payments\FedaPayPaymentProcessor;
use App\Services\Payments\FedaPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CourierPublicOrderController extends Controller
{
    public function index(Request $request): View
    {
        $courier = $this->courier($request);
        $available = Order::query()->with(['costumer', 'partner:id,name,phone', 'orderItems.product'])
            ->where('country_code', $courier->country_code)
            ->where('partner_order_status', 'ready_for_courier')->whereNull('user_id')
            ->latest()->paginate(20, ['*'], 'available_page');
        $assigned = Order::query()->with(['costumer', 'partner:id,name,phone', 'orderItems.product', 'payments'])
            ->where('user_id', (string) $courier->id)
            ->whereIn('partner_order_status', ['assigned_to_courier', 'handed_to_courier', 'out_for_delivery'])
            ->latest()->get();

        return view('livreurs.public-orders.index', compact('available', 'assigned'));
    }

    public function claim(Request $request, Order $order): RedirectResponse
    {
        $courier = $this->courier($request);
        DB::transaction(function () use ($order, $courier): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->country_code === $courier->country_code
                && $locked->partner_order_status === 'ready_for_courier' && $locked->user_id === null, 409,
                'Un autre livreur a déjà pris cette commande.');

            $locked->user_id = (string) $courier->id;
            $locked->assigned_at = now();
            $locked->take = true;
            $locked->status_order = true;
            $locked->partner_order_status = 'assigned_to_courier';
            $locked->save();
            OrderCourierHandoff::create([
                'order_id' => $locked->id,
                'partner_id' => $locked->partner_id,
                'courier_id' => $courier->id,
                'status' => 'assigned',
            ]);
        });

        return back()->with('success', 'La commande t’est affectée. Le partenaire va te remettre le colis.');
    }

    public function receive(Request $request, Order $order): RedirectResponse
    {
        $courier = $this->courier($request);
        DB::transaction(function () use ($order, $courier): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless((string) $locked->user_id === (string) $courier->id
                && $locked->partner_order_status === 'handed_to_courier', 409);
            $handoff = OrderCourierHandoff::query()->where('order_id', $locked->id)
                ->where('courier_id', $courier->id)->where('status', 'handed_over')->latest()->lockForUpdate()->firstOrFail();
            $handoff->update(['status' => 'received', 'received_by_courier' => $courier->id, 'received_at' => now()]);
            $locked->partner_order_status = 'out_for_delivery';
            $locked->save();
        });

        return back()->with('success', 'La réception du colis est enregistrée.');
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        $courier = $this->courier($request);
        $validated = $request->validate([
            'payment_method' => ['nullable', 'in:cash,mobile_money'],
            'transaction_reference' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($order, $courier, $validated): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless((string) $locked->user_id === (string) $courier->id
                && $locked->partner_order_status === 'out_for_delivery' && $locked->status !== 'delivered', 409);
            $handoff = OrderCourierHandoff::query()->where('order_id', $locked->id)
                ->where('courier_id', $courier->id)->where('status', 'received')
                ->where('received_by_courier', $courier->id)->latest()->lockForUpdate()->first();
            abort_unless($handoff, 409, 'Confirme d’abord la réception du colis avant de valider la livraison.');
            $balance = max(0, (int) $locked->montant - (int) $locked->paid_amount);
            if ($locked->payment_timing === 'on_delivery' && $balance > 0) {
                abort_unless(in_array($validated['payment_method'] ?? '', ['cash', 'mobile_money'], true), 422, 'Indique le moyen de paiement reçu.');
                if ($validated['payment_method'] === 'mobile_money') {
                    abort_unless($locked->country_code === 'TG', 422, 'Le paiement mobile FedaPay est actuellement configuré pour le Togo.');
                    abort_unless((int) $locked->paid_amount >= (int) $locked->montant, 409, 'Attends la confirmation du paiement Mobile Money avant de valider la livraison.');
                } else {
                    abort_if($locked->payments()->where('gateway', 'fedapay')->where('stage', 'on_delivery')
                        ->whereIn('status', ['initiating', 'pending'])->exists(), 409,
                        'Une demande Mobile Money est encore en cours. Attends son résultat avant de choisir les espèces.');
                    OrderPayment::create([
                        'order_id' => $locked->id,
                        'stage' => 'on_delivery',
                        'method' => 'cash',
                        'amount' => $balance,
                        'status' => 'collected',
                        'transaction_reference' => $validated['transaction_reference'] ?? null,
                        'collected_by' => $courier->id,
                        'collected_at' => now(),
                        'notes' => $validated['notes'] ?? null,
                    ]);
                    $locked->paid_amount = (int) $locked->paid_amount + $balance;
                }
            }
            $locked->status = 'delivered';
            $locked->partner_order_status = 'delivered';
            $locked->save();
        });

        return back()->with('success', 'La livraison est terminée et son paiement est enregistré.');
    }

    public function startMobilePayment(
        Request $request,
        Order $order,
        FedaPayService $fedapay
    ): JsonResponse {
        $courier = $this->courier($request);
        abort_unless($fedapay->isConfigured(), 503, 'Le paiement Sandbox FedaPay n’est pas encore configuré.');

        $validated = $request->validate([
            'provider_method' => ['required', 'in:mixx,moov'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[+0-9 ()-]+$/'],
            'email' => ['required', 'email', 'max:160'],
        ]);
        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (str_starts_with($phone, '228') && strlen($phone) === 11) {
            $phone = substr($phone, 3);
        }
        abort_unless((bool) preg_match('/^[0-9]{8}$/', $phone), 422, 'Saisis un numéro togolais valide à 8 chiffres.');
        $gatewayMethod = $validated['provider_method'] === 'mixx' ? 'togocel' : 'moov_tg';

        [$payment, $shouldStartGatewayRequest] = DB::transaction(function () use ($order, $courier, $gatewayMethod): array {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless((string) $locked->user_id === (string) $courier->id
                && $locked->country_code === 'TG'
                && $locked->payment_timing === 'on_delivery'
                && $locked->partner_order_status === 'out_for_delivery'
                && $locked->status !== 'delivered', 409, 'Cette commande ne peut pas recevoir un paiement mobile maintenant.');

            $handoff = OrderCourierHandoff::query()->where('order_id', $locked->id)
                ->where('courier_id', $courier->id)->where('status', 'received')
                ->where('received_by_courier', $courier->id)->lockForUpdate()->first();
            abort_unless($handoff, 409, 'Confirme d’abord la réception du colis.');

            $balance = max(0, (int) $locked->montant - (int) $locked->paid_amount);
            abort_if($balance < 1, 409, 'Cette commande est déjà réglée.');

            $existing = OrderPayment::query()->where('order_id', $locked->id)
                ->where('stage', 'on_delivery')->where('gateway', 'fedapay')
                ->whereIn('status', ['initiating', 'pending'])->lockForUpdate()->first();
            if ($existing) {
                return [$existing, false];
            }

            return [OrderPayment::create([
                'order_id' => $locked->id,
                'stage' => 'on_delivery',
                'method' => 'mobile_money',
                'amount' => $balance,
                'status' => 'initiating',
                'gateway' => 'fedapay',
                'gateway_method' => $gatewayMethod,
                'gateway_status' => 'creating',
                'collected_by' => $courier->id,
                'collected_at' => now(),
            ]), true];
        });

        if (! $shouldStartGatewayRequest) {
            return response()->json([
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'message' => $payment->gateway_transaction_id
                    ? 'Une demande existe déjà. Vérification de son état en cours.'
                    : 'La demande est en cours de préparation. Patiente quelques secondes avant de vérifier son état.',
                'status_url' => route('courier.public-orders.mobile-payment.status', [$order, $payment]),
            ], 202);
        }

        try {
            $transaction = $fedapay->createTransaction($order->fresh('costumer'), $phone, $validated['email']);
            $payment->gateway_transaction_id = (string) $transaction['id'];
            $payment->gateway_reference = isset($transaction['reference']) ? (string) $transaction['reference'] : null;
            $payment->transaction_reference = $payment->gateway_reference;
            $payment->gateway_status = (string) ($transaction['status'] ?? 'pending');
            $payment->status = 'pending';
            $payment->save();

            $fedapay->requestMobilePayment((string) $transaction['id'], $gatewayMethod);
            Log::info('FedaPay Mobile Money request started.', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'gateway_transaction_id' => $payment->gateway_transaction_id,
                'gateway_method' => $gatewayMethod,
            ]);
        } catch (\Throwable $exception) {
            if (! $payment->gateway_transaction_id) {
                $payment->status = 'failed';
                $payment->gateway_status = 'creation_failed';
                $payment->save();
            }
            Log::warning('FedaPay Mobile Money request could not be started.', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'gateway_transaction_id' => $payment->gateway_transaction_id,
                'exception' => get_class($exception),
            ]);

            return response()->json([
                'message' => $payment->gateway_transaction_id
                    ? 'La demande a été créée mais son envoi est incertain. Vérifie le téléphone du client avant de réessayer.'
                    : 'FedaPay Sandbox n’a pas pu créer la demande. Tu peux réessayer.',
            ], 502);
        }

        return response()->json([
            'payment_id' => $payment->id,
            'status' => 'pending',
            'message' => 'Demande envoyée. Le client doit confirmer sur son téléphone.',
            'status_url' => route('courier.public-orders.mobile-payment.status', [$order, $payment]),
        ], 202);
    }

    public function mobilePaymentStatus(
        Request $request,
        Order $order,
        OrderPayment $payment,
        FedaPayService $fedapay,
        FedaPayPaymentProcessor $processor
    ): JsonResponse {
        $courier = $this->courier($request);
        abort_unless((string) $order->user_id === (string) $courier->id
            && (int) $payment->order_id === (int) $order->id
            && $payment->gateway === 'fedapay', 404);

        if ($payment->gateway_transaction_id && $payment->status !== 'confirmed' && $fedapay->isConfigured()) {
            try {
                $payment = $processor->refresh(
                    $payment,
                    $fedapay->retrieveTransaction((string) $payment->gateway_transaction_id)
                );
            } catch (\Throwable $exception) {
                Log::warning('FedaPay transaction status could not be refreshed.', [
                    'payment_id' => $payment->id,
                    'gateway_transaction_id' => $payment->gateway_transaction_id,
                    'exception' => get_class($exception),
                ]);
            }
        }

        return response()->json([
            'status' => $payment->fresh()->status,
            'gateway_status' => $payment->fresh()->gateway_status,
            'paid_amount' => (int) $order->fresh()->paid_amount,
            'amount' => (int) $payment->amount,
            'message' => match ($payment->fresh()->status) {
                'confirmed' => 'Paiement confirmé par FedaPay. Tu peux terminer la livraison.',
                'failed' => 'Le paiement a échoué ou a été annulé. Vérifie avec le client avant de réessayer.',
                default => 'En attente de la confirmation du client sur son téléphone.',
            },
        ]);
    }

    private function courier(Request $request): User
    {
        $courier = $request->user();
        abort_unless($courier && $courier->user_type === User::ROLE_LIVREUR && $courier->country_code, 403);
        return $courier;
    }
}
