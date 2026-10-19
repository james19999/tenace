<?php

namespace App\Http\Controllers;

use App\Models\OrderCourierHandoff;
use App\Models\OrderPayment;
use App\Models\Costumer;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\ProductCountryPrice;
use App\Models\User;
use App\Support\WestAfricaShippingCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Gloudemans\Shoppingcart\Facades\Cart;

class PartnerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $partner = $this->partner($request);
        $orders = Order::query()->with(['costumer', 'user:id,name,phone', 'orderItems.product'])
            ->where('partner_id', $partner->id)
            ->latest()
            ->paginate(25);

        $counts = Order::query()->where('partner_id', $partner->id)
            ->selectRaw("SUM(CASE WHEN partner_order_status IN ('awaiting_delivery_quote', 'awaiting_customer_confirmation', 'awaiting_prepayment') THEN 1 ELSE 0 END) as attention_count")
            ->selectRaw("SUM(CASE WHEN partner_order_status IN ('preparing', 'ready_for_courier', 'assigned_to_courier', 'handed_to_courier', 'out_for_delivery') THEN 1 ELSE 0 END) as active_count")
            ->selectRaw("SUM(CASE WHEN partner_order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_count")
            ->first();

        return view('partners.orders.index', [
            'partner' => $partner,
            'countryName' => WestAfricaShippingCatalog::countries()[$partner->country_code]['label'] ?? $partner->country_code,
            'orders' => $orders,
            'counts' => $counts,
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $partner = $this->partner($request);
        abort_unless((int) $order->partner_id === (int) $partner->id, 404);

        $order->load(['costumer', 'orderItems.product', 'user:id,name,phone,country_code', 'payments.collector', 'payments.confirmer', 'courierHandoffs.courier']);
        $couriers = User::query()->where('user_type', User::ROLE_LIVREUR)
            ->where('country_code', $partner->country_code)->where('active', 1)->orderBy('name')->get(['id', 'name', 'phone']);

        return view('partners.orders.show', compact('partner', 'order', 'couriers'));
    }

    public function storeFromCart(Request $request): RedirectResponse
    {
        $partner = $this->partner($request);
        $countries = WestAfricaShippingCatalog::countries();
        $country = $countries[$partner->country_code] ?? null;
        abort_unless($country, 403, 'Le pays de ce compte partenaire n’est pas configuré.');

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:160'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:160'],
            'address' => ['required', 'string', 'max:180'],
            'city' => ['required', Rule::in($country['cities'])],
            'delivery_fee' => ['required', 'integer', 'min:0', 'max:999999999'],
            'payment_timing' => ['required', Rule::in(['before_delivery', 'on_delivery'])],
            'payment_method' => ['required', Rule::in(['cash', 'mobile_money'])],
        ]);

        $cartItems = Cart::instance('cart')->content();
        abort_if($cartItems->isEmpty(), 422, 'Ajoute au moins un produit au panier.');

        $order = DB::transaction(function () use ($validated, $partner, $cartItems): Order {
            $productIds = $cartItems->map(fn ($item) => (int) $item->id)->unique()->values();
            $prices = ProductCountryPrice::query()->where('country_code', $partner->country_code)
                ->where('is_available', true)->whereIn('product_id', $productIds)->lockForUpdate()->get()->keyBy('product_id');
            abort_unless($prices->count() === $productIds->count(), 409, 'Un produit du panier n’est plus proposé dans ton pays. Actualise le catalogue.');

            $subtotal = 0;
            foreach ($cartItems as $item) {
                $subtotal += (int) $prices[(int) $item->id]->price * (int) $item->qty;
            }
            $deliveryFee = (int) $validated['delivery_fee'];
            $customer = Costumer::create([
                'name' => $validated['customer_name'],
                'phone' => trim($validated['customer_phone']),
                'email' => $validated['customer_email'] ?? null,
                'adresse' => trim($validated['address']).', '.$validated['city'].', '.$partner->country_code,
                'user_id' => $partner->id,
            ]);

            $order = new Order();
            $order->costumer_id = $customer->id;
            $order->subtotal = $subtotal;
            $order->tax = $deliveryFee;
            $order->total = $subtotal;
            $order->montant = $subtotal + $deliveryFee;
            $order->paid_amount = 0;
            $order->remis = 0;
            $order->code = 'PT-'.Str::upper(Str::random(10));
            $order->time = 'À organiser';
            $order->date_order = now()->toDateString();
            $order->type = 'PU';
            $order->brouillon = 0;
            $order->status = 'ordered';
            $order->status_order = false;
            $order->take = false;
            $order->user_id = null;
            $order->created_user = $partner->id;
            $order->order_by = $partner->id;
            $order->delivery_mode = 'local';
            $order->shipping_country = $partner->country_code;
            $order->shipping_city = $validated['city'];
            $order->country_code = $partner->country_code;
            $order->partner_id = $partner->id;
            $order->payment_timing = $validated['payment_timing'];
            $order->requested_payment_method = $validated['payment_method'];
            $order->public_tracking_token = (string) Str::uuid();
            $order->partner_order_status = $validated['payment_timing'] === 'before_delivery' ? 'awaiting_prepayment' : 'preparing';
            $order->save();

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'product_id' => (int) $item->id,
                    'order_id' => $order->id,
                    'price' => (int) $prices[(int) $item->id]->price,
                    'quantity' => (int) $item->qty,
                    'high_price' => 0,
                ]);
            }

            return $order;
        });

        Cart::instance('cart')->destroy();

        return redirect()->route('partner.orders.show', $order)
            ->with('success', 'La commande est créée avec les prix de '.$country['label'].'.');
    }

    public function setDeliveryFee(Request $request, Order $order): RedirectResponse
    {
        $partner = $this->partner($request);
        abort_unless((int) $order->partner_id === (int) $partner->id, 404);
        $validated = $request->validate(['delivery_fee' => ['required', 'integer', 'min:0', 'max:999999999']]);

        DB::transaction(function () use ($order, $partner, $validated): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $locked->partner_id === (int) $partner->id && $locked->partner_order_status === 'awaiting_delivery_quote', 409);

            $locked->tax = (int) $validated['delivery_fee'];
            $locked->total = (int) $locked->subtotal + (int) $validated['delivery_fee'];
            $locked->montant = $locked->total;
            $locked->partner_order_status = 'awaiting_customer_confirmation';
            $locked->save();
        });

        return back()->with('success', 'Les frais de livraison sont envoyés au client pour confirmation.');
    }

    public function confirmPayment(Request $request, Order $order, OrderPayment $payment): RedirectResponse
    {
        $partner = $this->partner($request);
        abort_unless((int) $order->partner_id === (int) $partner->id && (int) $payment->order_id === (int) $order->id, 404);

        DB::transaction(function () use ($order, $payment, $partner): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $lockedPayment = OrderPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedPayment->status === 'awaiting_partner_confirmation' && $lockedPayment->stage === 'before_delivery', 409);

            $lockedPayment->status = 'confirmed';
            $lockedPayment->confirmed_by = $partner->id;
            $lockedPayment->confirmed_at = now();
            $lockedPayment->save();

            $lockedOrder->paid_amount = (int) $lockedOrder->paid_amount + (int) $lockedPayment->amount;
            if ($lockedOrder->paid_amount >= (int) $lockedOrder->montant) {
                $lockedOrder->partner_order_status = 'preparing';
            }
            $lockedOrder->save();
        });

        return back()->with('success', 'Le paiement anticipé est confirmé.');
    }

    public function markReady(Request $request, Order $order): RedirectResponse
    {
        $partner = $this->partner($request);
        abort_unless((int) $order->partner_id === (int) $partner->id, 404);

        $changed = Order::query()->whereKey($order->id)->where('partner_order_status', 'preparing')
            ->update(['partner_order_status' => 'ready_for_courier', 'updated_at' => now()]);
        abort_unless($changed === 1, 409, 'Cette commande n’est pas encore prête à être confiée à un livreur.');

        return back()->with('success', 'La commande est proposée aux livreurs de ce pays.');
    }

    public function assignCourier(Request $request, Order $order): RedirectResponse
    {
        $partner = $this->partner($request);
        abort_unless((int) $order->partner_id === (int) $partner->id, 404);
        $validated = $request->validate(['courier_id' => ['required', 'integer', 'exists:users,id']]);

        DB::transaction(function () use ($order, $partner, $validated): void {
            $courier = User::query()->whereKey($validated['courier_id'])
                ->where('user_type', User::ROLE_LIVREUR)
                ->where('country_code', $partner->country_code)
                ->where('active', 1)->firstOrFail();
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $locked->partner_id === (int) $partner->id && $locked->partner_order_status === 'ready_for_courier' && ! $locked->user_id, 409);

            $locked->user_id = (string) $courier->id;
            $locked->assigned_at = now();
            $locked->take = true;
            $locked->status_order = true;
            $locked->partner_order_status = 'assigned_to_courier';
            $locked->save();

            OrderCourierHandoff::create([
                'order_id' => $locked->id,
                'partner_id' => $partner->id,
                'courier_id' => $courier->id,
                'status' => 'assigned',
            ]);
        });

        return back()->with('success', 'La commande a été affectée au livreur choisi.');
    }

    public function handover(Request $request, Order $order): RedirectResponse
    {
        $partner = $this->partner($request);
        abort_unless((int) $order->partner_id === (int) $partner->id, 404);

        DB::transaction(function () use ($order, $partner): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $locked->partner_id === (int) $partner->id && $locked->partner_order_status === 'assigned_to_courier' && $locked->user_id, 409);
            $handoff = OrderCourierHandoff::query()->where('order_id', $locked->id)->where('courier_id', $locked->user_id)->latest()->lockForUpdate()->firstOrFail();
            abort_unless($handoff->status === 'assigned', 409);

            $handoff->status = 'handed_over';
            $handoff->handed_over_by = $partner->id;
            $handoff->handed_over_at = now();
            $handoff->save();
            $locked->partner_order_status = 'handed_to_courier';
            $locked->save();
        });

        return back()->with('success', 'La remise du colis au livreur est enregistrée.');
    }

    public function confirmCashRemittance(Request $request, Order $order, OrderPayment $payment): RedirectResponse
    {
        $partner = $this->partner($request);
        abort_unless((int) $order->partner_id === (int) $partner->id && (int) $payment->order_id === (int) $order->id, 404);
        abort_unless($payment->stage === 'on_delivery' && $payment->status === 'collected', 409);
        $payment->update(['status' => 'confirmed', 'confirmed_by' => $partner->id, 'confirmed_at' => now()]);

        return back()->with('success', 'La remise de l’argent collecté est confirmée.');
    }

    private function partner(Request $request): User
    {
        $partner = $request->user();
        abort_unless($partner && $partner->user_type === User::ROLE_PARTNER && $partner->country_code, 403);

        return $partner;
    }
}
