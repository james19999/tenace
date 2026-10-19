<?php

namespace App\Http\Controllers;

use App\Models\OrderCourierHandoff;
use App\Models\OrderPayment;
use App\Models\Orders\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $assigned = Order::query()->with(['costumer', 'partner:id,name,phone', 'orderItems.product'])
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
                OrderPayment::create([
                    'order_id' => $locked->id,
                    'stage' => 'on_delivery',
                    'method' => $validated['payment_method'],
                    'amount' => $balance,
                    'status' => 'collected',
                    'transaction_reference' => $validated['transaction_reference'] ?? null,
                    'collected_by' => $courier->id,
                    'collected_at' => now(),
                    'notes' => $validated['notes'] ?? null,
                ]);
                $locked->paid_amount = (int) $locked->paid_amount + $balance;
            }
            $locked->status = 'delivered';
            $locked->partner_order_status = 'delivered';
            $locked->save();
        });

        return back()->with('success', 'La livraison est terminée et son paiement est enregistré.');
    }

    private function courier(Request $request): User
    {
        $courier = $request->user();
        abort_unless($courier && $courier->user_type === User::ROLE_LIVREUR && $courier->country_code, 403);
        return $courier;
    }
}
