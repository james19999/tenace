<?php

namespace App\Http\Controllers;

use App\Models\OrderCourierHandoff;
use App\Models\OrderPayment;
use App\Models\Costumer;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\ProductCountryPrice;
use App\Models\PartnerProductStock;
use App\Models\PartnerCourierStockIssueLine;
use App\Models\User;
use App\Support\WestAfricaShippingCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\PartnerCourierStockService;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Gloudemans\Shoppingcart\Facades\Cart;

class PartnerOrderController extends Controller
{
    public function couriers(Request $request): View
    {
        $partner = $this->partner($request);
        $couriers = User::query()->with('courierDocuments')
            ->where('user_type', User::ROLE_LIVREUR)
            ->where('country_code', strtoupper((string) $partner->country_code))
            ->orderBy('name')->paginate(20);
        $partnerStockProducts = PartnerProductStock::query()->with('product:id,name')
            ->where('partner_id', $partner->id)->where('country_code', strtoupper((string) $partner->country_code))
            ->whereHas('product.countryPrices', fn ($query) => $query->where('country_code', strtoupper((string) $partner->country_code))->where('is_available', true))
            ->orderBy('product_id')->get();
        $courierStock = PartnerCourierStockIssueLine::query()->selectRaw('partner_courier_stock_issues.courier_id, partner_courier_stock_issue_lines.product_id, SUM(partner_courier_stock_issue_lines.quantity_remaining) as quantity')
            ->join('partner_courier_stock_issues', 'partner_courier_stock_issues.id', '=', 'partner_courier_stock_issue_lines.issue_id')
            ->where('partner_courier_stock_issues.partner_id', $partner->id)
            ->whereIn('partner_courier_stock_issues.courier_id', $couriers->getCollection()->pluck('id'))
            ->groupBy('partner_courier_stock_issues.courier_id', 'partner_courier_stock_issue_lines.product_id')
            ->get()->groupBy('courier_id');

        return view('partners.couriers.index', compact('partner', 'couriers', 'partnerStockProducts', 'courierStock'));
    }

    public function issueCourierStock(Request $request, int $courierId): RedirectResponse
    {
        $partner = $this->partner($request);
        $courier = $this->courierInPartnerCountry($partner, $courierId);
        $validated = $request->validate([
            'quantities' => ['required', 'array', 'min:1'],
            'quantities.*' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);
        $quantities = array_filter($validated['quantities'], fn ($quantity) => (int) $quantity > 0);
        if (! $quantities) return back()->withErrors(['quantities' => 'Indique au moins une quantité à remettre.']);
        foreach (array_keys($quantities) as $productId) {
            abort_unless(PartnerProductStock::query()->where('partner_id', $partner->id)->where('product_id', $productId)
                ->where('country_code', strtoupper((string) $partner->country_code))
                ->whereHas('product.countryPrices', fn ($query) => $query->where('country_code', strtoupper((string) $partner->country_code))->where('is_available', true))
                ->exists(), 422);
        }

        try {
            app(PartnerCourierStockService::class)->issue($partner, $courier, $request->user(), $quantities);
        } catch (\DomainException $exception) {
            return back()->withErrors(['quantities' => $exception->getMessage()]);
        }

        return back()->with('success', 'Les quantités ont été remises au livreur. Le stock disponible du partenaire a été mis à jour.');
    }

    public function createCourier(Request $request): View
    {
        $partner = $this->partner($request);

        return view('partners.couriers.create', [
            'partner' => $partner,
            'countryName' => WestAfricaShippingCatalog::countries()[$partner->country_code]['label'] ?? $partner->country_code,
        ]);
    }

    public function storeCourier(Request $request): RedirectResponse
    {
        $partner = $this->partner($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'adresse' => ['required', 'string', 'max:255'],
            'emergency_contact_name' => ['required', 'string', 'max:160'],
            'emergency_contact_phone' => ['required', 'string', 'max:32'],
            'identity_card_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $courier = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'],
            'adresse' => $data['adresse'],
            'user_type' => User::ROLE_LIVREUR,
            'country_code' => strtoupper((string) $partner->country_code),
            'emergency_contact_name' => $data['emergency_contact_name'],
            'emergency_contact_phone' => $data['emergency_contact_phone'],
            'active' => true,
        ]);

        $courier->courierDocuments()->create([
            'document_type' => 'identity_card',
            'path' => $data['identity_card_photo']->store('courier-documents', 'local'),
            'original_name' => $data['identity_card_photo']->getClientOriginalName(),
            'uploaded_by' => $partner->id,
        ]);

        return redirect()->route('partner.couriers.index')->with('success', 'Le compte du livreur a été créé. Il est rattaché à '.$partner->country_code.'.');
    }

    public function editCourier(Request $request, int $courierId): View
    {
        $partner = $this->partner($request);
        $courier = $this->courierInPartnerCountry($partner, $courierId)->load('courierDocuments');

        return view('partners.couriers.edit', [
            'partner' => $partner,
            'courier' => $courier,
            'countryName' => WestAfricaShippingCatalog::countries()[$partner->country_code]['label'] ?? $partner->country_code,
        ]);
    }

    public function updateCourier(Request $request, int $courierId): RedirectResponse
    {
        $partner = $this->partner($request);
        $courier = $this->courierInPartnerCountry($partner, $courierId);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($courier->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($courier->id)],
            'adresse' => ['required', 'string', 'max:255'],
            'emergency_contact_name' => ['required', 'string', 'max:160'],
            'emergency_contact_phone' => ['required', 'string', 'max:32'],
            'identity_card_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $courier->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'adresse' => $data['adresse'],
            'emergency_contact_name' => $data['emergency_contact_name'],
            'emergency_contact_phone' => $data['emergency_contact_phone'],
        ]);
        if (! empty($data['password'])) {
            $courier->password = Hash::make($data['password']);
        }
        $courier->save();

        if (! empty($data['identity_card_photo'])) {
            $courier->courierDocuments()->create([
                'document_type' => 'identity_card',
                'path' => $data['identity_card_photo']->store('courier-documents', 'local'),
                'original_name' => $data['identity_card_photo']->getClientOriginalName(),
                'uploaded_by' => $partner->id,
            ]);
        }

        return redirect()->route('partner.couriers.index')->with('success', 'Les informations du livreur ont été mises à jour.');
    }

    public function deactivateCourier(Request $request, int $courierId): RedirectResponse
    {
        $partner = $this->partner($request);
        $courier = $this->courierInPartnerCountry($partner, $courierId);
        abort_if((int) $courier->id === (int) $partner->id, 409, 'Tu ne peux pas désactiver ton propre compte.');
        $hasActiveDeliveries = Order::query()->where('user_id', $courier->id)
            ->whereNotIn('status', ['delivered', 'canceled'])
            ->exists();
        if ($hasActiveDeliveries) {
            return back()->with('error', 'Ce livreur a encore des commandes en cours. Termine ou réaffecte-les avant de désactiver son compte.');
        }
        $courier->active = false;
        $courier->save();

        return redirect()->route('partner.couriers.index')->with('success', 'Le compte du livreur a été désactivé. Son historique de livraisons est conservé.');
    }

    public function reactivateCourier(Request $request, int $courierId): RedirectResponse
    {
        $partner = $this->partner($request);
        $courier = $this->courierInPartnerCountry($partner, $courierId);
        $courier->active = true;
        $courier->save();

        return redirect()->route('partner.couriers.index')->with('success', 'Le compte du livreur est de nouveau actif.');
    }

    public function showCourierDocument(Request $request, int $courierId, int $documentId)
    {
        $partner = $this->partner($request);
        $courier = $this->courierInPartnerCountry($partner, $courierId);
        $document = $courier->courierDocuments()->findOrFail($documentId);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return response()->file(Storage::disk('local')->path($document->path), [
            'Content-Disposition' => 'inline; filename="'.addslashes($document->original_name ?: basename($document->path)).'"',
        ]);
    }

    private function courierInPartnerCountry(User $partner, int $courierId): User
    {
        return User::query()->where('user_type', User::ROLE_LIVREUR)
            ->where('country_code', strtoupper((string) $partner->country_code))
            ->findOrFail($courierId);
    }

    public function index(Request $request): View
    {
        $partner = $this->partner($request);
        $scope = $request->query('scope', 'active');
        abort_unless(in_array($scope, ['active', 'history', 'all'], true), 404);
        $query = Order::query()->with(['costumer', 'user:id,name,phone', 'orderItems.product'])
            ->where('partner_id', $partner->id);
        if ($scope === 'active') {
            $query->whereNotIn('partner_order_status', ['delivered', 'canceled']);
        } elseif ($scope === 'history') {
            $query->whereIn('partner_order_status', ['delivered', 'canceled']);
        }
        $orders = $query->latest()->paginate(25)->withQueryString();
        $orderCounts = [
            'active' => Order::query()->where('partner_id', $partner->id)->whereNotIn('partner_order_status', ['delivered', 'canceled'])->count(),
            'history' => Order::query()->where('partner_id', $partner->id)->whereIn('partner_order_status', ['delivered', 'canceled'])->count(),
            'all' => Order::query()->where('partner_id', $partner->id)->count(),
        ];

        return view('partners.orders.index', [
            'partner' => $partner,
            'countryName' => WestAfricaShippingCatalog::countries()[$partner->country_code]['label'] ?? $partner->country_code,
            'orders' => $orders,
            'scope' => $scope,
            'orderCounts' => $orderCounts,
        ]);
    }

    public function dashboard(Request $request): View
    {
        $partner = $this->partner($request);
        $countryName = WestAfricaShippingCatalog::countries()[$partner->country_code]['label'] ?? $partner->country_code;
        $revenueBase = Order::query()
            ->where('partner_id', $partner->id)
            ->where('partner_order_status', 'delivered')
            ->whereNotNull('delivered_at');
        $todayStart = now()->startOfDay();
        $tomorrow = $todayStart->copy()->addDay();
        $weekStart = now()->startOfWeek();
        $nextWeek = $weekStart->copy()->addWeek();
        $yearStart = now()->startOfYear();
        $nextYear = $yearStart->copy()->addYear();
        $revenuePeriods = collect([
            'today' => ['label' => 'Aujourd’hui', 'start' => $todayStart, 'end' => $tomorrow],
            'week' => ['label' => 'Cette semaine', 'start' => $weekStart, 'end' => $nextWeek],
            'year' => ['label' => 'Cette année', 'start' => $yearStart, 'end' => $nextYear],
        ])->map(function (array $period) use ($revenueBase): array {
            $totals = (clone $revenueBase)
                ->where('delivered_at', '>=', $period['start'])
                ->where('delivered_at', '<', $period['end'])
                ->selectRaw("COUNT(*) as orders_count, COALESCE(SUM(CAST(COALESCE(NULLIF(subtotal, ''), '0') AS DECIMAL(15,2))), 0) as amount")
                ->first();

            return $period + ['orders_count' => (int) $totals->orders_count, 'amount' => (int) $totals->amount];
        });

        $monthlyValues = array_fill(1, 12, 0);
        $monthlyRows = Order::query()->where('partner_id', $partner->id)->where('partner_order_status', 'delivered')
            ->where('delivered_at', '>=', $yearStart)->where('delivered_at', '<', $nextYear)
            ->selectRaw("MONTH(delivered_at) as period_month, COALESCE(SUM(CAST(COALESCE(NULLIF(subtotal, ''), '0') AS DECIMAL(15,2))), 0) as amount")
            ->groupByRaw('MONTH(delivered_at)')->get();
        foreach ($monthlyRows as $row) {
            $monthlyValues[(int) $row->period_month] = (int) $row->amount;
        }
        $monthlyChartValues = array_values($monthlyValues);
        $monthLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

        $firstDay = now()->subDays(13)->startOfDay();
        $dayTotals = Order::query()->where('partner_id', $partner->id)->where('partner_order_status', 'delivered')
            ->where('delivered_at', '>=', $firstDay)
            ->selectRaw('DATE(delivered_at) as period_day, COUNT(*) as orders_count')
            ->groupByRaw('DATE(delivered_at)')->get()->keyBy('period_day');
        $dailyLabels = [];
        $dailyOrderCounts = [];
        for ($day = $firstDay->copy(); $day <= now()->startOfDay(); $day->addDay()) {
            $dateKey = $day->toDateString();
            $dailyLabels[] = $day->format('d/m');
            $dailyOrderCounts[] = (int) ($dayTotals[$dateKey]->orders_count ?? 0);
        }

        $statusLabels = [
            'awaiting_delivery_quote' => 'Frais à définir',
            'awaiting_customer_confirmation' => 'Accord client',
            'awaiting_prepayment' => 'Paiement attendu',
            'preparing' => 'En préparation',
            'ready_for_courier' => 'Prête au retrait',
            'assigned_to_courier' => 'Livreur affecté',
            'handed_to_courier' => 'Colis remis',
            'out_for_delivery' => 'En livraison',
            'delivered' => 'Livrée',
            'canceled' => 'Annulée',
        ];
        $statusRows = Order::query()->where('partner_id', $partner->id)
            ->selectRaw('partner_order_status, COUNT(*) as orders_count')
            ->groupBy('partner_order_status')->get()->keyBy('partner_order_status');
        $ordersByStatusLabels = [];
        $ordersByStatusValues = [];
        foreach ($statusLabels as $status => $label) {
            $count = (int) ($statusRows[$status]->orders_count ?? 0);
            if ($count > 0) {
                $ordersByStatusLabels[] = $label;
                $ordersByStatusValues[] = $count;
            }
        }
        $attentionCount = (int) ($statusRows['awaiting_delivery_quote']->orders_count ?? 0)
            + (int) ($statusRows['awaiting_customer_confirmation']->orders_count ?? 0)
            + (int) ($statusRows['awaiting_prepayment']->orders_count ?? 0);
        $activeCount = (int) ($statusRows['preparing']->orders_count ?? 0)
            + (int) ($statusRows['ready_for_courier']->orders_count ?? 0)
            + (int) ($statusRows['assigned_to_courier']->orders_count ?? 0)
            + (int) ($statusRows['handed_to_courier']->orders_count ?? 0)
            + (int) ($statusRows['out_for_delivery']->orders_count ?? 0);
        $activeCourierCount = User::query()
            ->where('user_type', User::ROLE_LIVREUR)
            ->where('country_code', strtoupper((string) $partner->country_code))
            ->where('active', true)
            ->count();

        $recentOrders = Order::query()->with('costumer')
            ->where('partner_id', $partner->id)->latest()->limit(6)->get();

        return view('partners.dashboard', compact(
            'partner', 'countryName', 'revenuePeriods', 'monthlyChartValues', 'dailyLabels', 'dailyOrderCounts',
            'ordersByStatusLabels', 'ordersByStatusValues', 'attentionCount', 'activeCount', 'activeCourierCount', 'recentOrders', 'monthLabels'
        ));
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
            'customer_anonymous' => ['sometimes', 'boolean'],
            'customer_name' => ['required_unless:customer_anonymous,1', 'nullable', 'string', 'max:160'],
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
            $isAnonymousCustomer = (bool) ($validated['customer_anonymous'] ?? false);
            $customer = Costumer::create([
                'name' => $isAnonymousCustomer ? $this->generateAnonymousCustomerName() : $validated['customer_name'],
                'phone' => trim($validated['customer_phone']),
                'email' => $isAnonymousCustomer ? null : ($validated['customer_email'] ?? null),
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
            $order->delivery_fee_mode = 'per_order';
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
                    'delivery_fee' => 0,
                    'delivery_fee_is_override' => false,
                ]);
            }

            return $order;
        });

        Cart::instance('cart')->destroy();

        return redirect()->route('partner.orders.show', $order)
            ->with('success', 'La commande est créée avec les prix de '.$country['label'].'.');
    }

    private function generateAnonymousCustomerName(): string
    {
        do {
            $name = 'CL-'.Str::upper(Str::random(6));
        } while (Costumer::query()->where('name', $name)->exists());

        return $name;
    }

    public function setDeliveryFee(Request $request, Order $order): RedirectResponse
    {
        $partner = $this->partner($request);
        abort_unless((int) $order->partner_id === (int) $partner->id, 404);
        $validated = $request->validate([
            'delivery_fee' => ['required', 'integer', 'min:0', 'max:999999999'],
            'customer_informed' => ['accepted'],
        ]);

        DB::transaction(function () use ($order, $partner, $validated): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $locked->partner_id === (int) $partner->id && $locked->partner_order_status === 'awaiting_delivery_quote', 409);

            $locked->tax = (int) $validated['delivery_fee'];
            $locked->total = (int) $locked->subtotal + (int) $validated['delivery_fee'];
            $locked->montant = $locked->total;
            $locked->partner_order_status = $locked->payment_timing === 'before_delivery'
                ? 'awaiting_prepayment'
                : 'ready_for_courier';
            $locked->save();
        });

        return back()->with('success', 'Le total communiqué au client est enregistré.'.($order->payment_timing === 'before_delivery'
            ? ' La commande attend la confirmation du paiement.'
            : ' La commande est maintenant visible par les livreurs du pays.'));
    }

    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        $partner = $this->partner($request);
        abort_unless((int) $order->partner_id === (int) $partner->id, 404);
        $validated = $request->validate(['motif' => ['required', 'string', 'max:255']], [
            'motif.required' => 'Indique le motif d’annulation.',
        ]);

        DB::transaction(function () use ($order, $partner, $validated): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $locked->partner_id === (int) $partner->id, 404);
            abort_unless(! in_array($locked->partner_order_status, ['delivered', 'canceled'], true), 409, 'Cette commande est déjà terminée ou annulée.');
            abort_if((int) $locked->paid_amount > 0 || $locked->payments()->whereIn('status', ['initiating', 'pending', 'awaiting_partner_confirmation', 'confirmed', 'collected'])->exists(), 409,
                'Un paiement est en cours, déclaré ou déjà encaissé. Vérifie-le et régularise le remboursement avant d’annuler cette commande.');

            $locked->partner_order_status = 'canceled';
            $locked->status = 'canceled';
            $locked->motif = trim($validated['motif']);
            $locked->status_order = false;
            $locked->take = false;
            $locked->save();

            $locked->courierHandoffs()->whereIn('status', ['assigned', 'handed_over', 'received'])->update([
                'status' => 'canceled',
                'notes' => trim($validated['motif']),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('partner.orders.index', ['scope' => 'history'])->with('success', 'La commande est annulée.');
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
                $lockedOrder->partner_order_status = 'ready_for_courier';
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

        $stockService = app(PartnerCourierStockService::class);
        DB::transaction(function () use ($order, $partner, $validated, $stockService): void {
            $courier = User::query()->whereKey($validated['courier_id'])
                ->where('user_type', User::ROLE_LIVREUR)
                ->where('country_code', $partner->country_code)
                ->where('active', 1)->firstOrFail();
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $locked->partner_id === (int) $partner->id && $locked->partner_order_status === 'ready_for_courier' && ! $locked->user_id, 409);
            $locked->load('orderItems');
            abort_unless($stockService->hasForOrder($courier, $locked), 409,
                'Le stock remis à ce livreur ne suffit pas pour cette commande.');

            $locked->user_id = (string) $courier->id;
            $locked->assigned_at = now();
            $locked->take = true;
            $locked->status_order = true;
            $locked->partner_order_status = 'out_for_delivery';
            $locked->save();

            OrderCourierHandoff::create([
                'order_id' => $locked->id,
                'partner_id' => $partner->id,
                'courier_id' => $courier->id,
                'status' => 'received',
                'received_by_courier' => $courier->id,
                'received_at' => now(),
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
