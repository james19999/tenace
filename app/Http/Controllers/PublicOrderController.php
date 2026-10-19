<?php

namespace App\Http\Controllers;

use App\Models\ProductCountryPrice;
use App\Models\PublicOrderLink;
use App\Models\User;
use App\Models\Costumer;
use App\Models\CustomerPortalAccount;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Support\WestAfricaShippingCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\OrderPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicOrderController extends Controller
{
    public function app(Request $request): View
    {
        $savedCustomer = $this->portalCustomer($request);

        return view('public-orders.show', [
            'link' => null,
            'countries' => WestAfricaShippingCatalog::countries(),
            'savedCustomer' => $savedCustomer,
            'savedAddress' => $this->portalAddress($savedCustomer),
        ]);
    }

    public function show(Request $request, string $token): View
    {
        $link = $this->availableLink($token);
        $savedCustomer = $this->portalCustomer($request);

        return view('public-orders.show', [
            'link' => $link,
            'countries' => WestAfricaShippingCatalog::countries(),
            'savedCustomer' => $savedCustomer,
            'savedAddress' => $this->portalAddress($savedCustomer),
        ]);
    }

    public function manifest(?string $token = null): JsonResponse
    {
        if ($token !== null) {
            $this->availableLink($token);
        }

        return response()->json([
            // The installed app starts from its permanent catalogue, independent of invitation links.
            'id' => url('/commander'),
            'name' => 'TENACOS',
            'short_name' => 'TENACOS',
            'description' => 'Catalogue et commandes TENACOS',
            'prefer_related_applications' => false,
            'related_applications' => [
                ['platform' => 'webapp', 'id' => url('/commander')],
            ],
            'start_url' => route('public-order.app'),
            'scope' => url('/').'/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#ffffff',
            'theme_color' => '#7e1615',
            'icons' => [
                ['src' => asset('icons/icon-192x192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('icons/icon-512x512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ],
        ])->header('Content-Type', 'application/manifest+json; charset=utf-8')
            ->header('Cache-Control', 'no-store');
    }

    public function catalog(Request $request, ?string $token = null): JsonResponse
    {
        $link = $token !== null ? $this->availableLink($token) : null;
        $countries = WestAfricaShippingCatalog::countries();
        $countryCode = strtoupper((string) $request->query('country', ''));
        abort_unless(isset($countries[$countryCode]), 422, 'Choisis un pays valide.');

        $partner = User::query()
            ->where('user_type', User::ROLE_PARTNER)
            ->where('country_code', $countryCode)
            ->where('active', 1)
            ->orderBy('id')
            ->first();

        if (! $partner) {
            return response()->json([
                'message' => 'Aucun partenaire ne prend actuellement les commandes dans ce pays.',
                'products' => [],
                'cities' => $countries[$countryCode]['cities'],
            ], 422);
        }

        $featuredIds = $link
            ? $link->products()->pluck('products.id')->map(fn ($id) => (int) $id)->all()
            : [];
        $prices = ProductCountryPrice::query()
            ->with('product:id,name,description,img')
            ->where('country_code', $countryCode)
            ->where('is_available', true)
            ->whereHas('product')
            ->get()
            ->filter(fn (ProductCountryPrice $price) => $price->product)
            ->sortBy(fn (ProductCountryPrice $price) => [in_array($price->product_id, $featuredIds, true) ? 0 : 1, $price->product->name])
            ->values()
            ->map(fn (ProductCountryPrice $price) => [
                'id' => $price->product_id,
                'name' => $price->product->name,
                'description' => $price->product->description,
                'image' => $price->product->img ? url('image/'.$price->product->img) : null,
                'price' => (int) $price->price,
                'featured' => in_array((int) $price->product_id, $featuredIds, true),
            ]);

        return response()->json([
            'country' => ['code' => $countryCode, 'name' => $countries[$countryCode]['label']],
            'cities' => $countries[$countryCode]['cities'],
            'products' => $prices,
        ])->header('Cache-Control', 'no-store');
    }

    public function placeOrder(Request $request, ?string $token = null): RedirectResponse
    {
        $link = $token !== null ? $this->availableLink($token) : null;
        $countries = WestAfricaShippingCatalog::countries();
        $countryCode = strtoupper((string) $request->input('country_code'));
        $cities = $countries[$countryCode]['cities'] ?? [];
        $portalAccountId = $request->session()->get('customer_portal_account_id');
        $portalAccount = $portalAccountId ? CustomerPortalAccount::find($portalAccountId) : null;
        $portalCustomer = $portalAccount?->customers()->orderByDesc('costumers.id')->first();
        $customerDetailsRequired = ! $portalCustomer;

        $validated = $request->validate([
            'country_code' => ['required', Rule::in(array_keys($countries))],
            'city' => ['required', Rule::in($cities)],
            'name' => [$customerDetailsRequired ? 'required' : 'nullable', 'string', 'max:160'],
            'phone' => [$customerDetailsRequired ? 'required' : 'nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:160'],
            'address' => ['required', 'string', 'max:180'],
            'payment_timing' => ['required', Rule::in(['before_delivery', 'on_delivery'])],
            'payment_method' => ['required', Rule::in(['cash', 'mobile_money'])],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ], [
            'city.in' => 'Choisis une ville proposée pour le pays sélectionné.',
            'items.required' => 'Ajoute au moins un produit à ta commande.',
        ]);

        $partner = User::query()
            ->where('user_type', User::ROLE_PARTNER)
            ->where('country_code', $countryCode)
            ->where('active', 1)
            ->orderBy('id')
            ->first();

        if (! $partner) {
            return back()->withErrors(['country_code' => 'Aucun partenaire ne prend actuellement les commandes dans ce pays.'])->withInput();
        }

        $quantities = collect($validated['items'])->keyBy(fn ($item) => (int) $item['product_id']);
        if ($quantities->count() !== count($validated['items'])) {
            return back()->withErrors(['items' => 'Un même produit ne peut apparaître qu’une seule fois.'])->withInput();
        }

        $prices = ProductCountryPrice::query()
            ->with('product')
            ->where('country_code', $countryCode)
            ->where('is_available', true)
            ->whereIn('product_id', $quantities->keys())
            ->get()
            ->keyBy('product_id');

        if ($prices->count() !== $quantities->count()) {
            return back()->withErrors(['items' => 'Un produit sélectionné n’est plus disponible dans ce pays.'])->withInput();
        }

        $trackingToken = (string) Str::uuid();
        $order = DB::transaction(function () use ($validated, $countryCode, $partner, $link, $prices, $quantities, $trackingToken, $portalAccount, $portalCustomer): Order {
            $subtotal = 0;
            foreach ($quantities as $productId => $item) {
                $subtotal += (int) $prices[$productId]->price * (int) $item['quantity'];
            }

            $customer = Costumer::create([
                'name' => $portalCustomer?->name ?? ($validated['name'] ?? ''),
                'phone' => $portalCustomer?->phone ?? trim((string) ($validated['phone'] ?? '')),
                'email' => $portalCustomer?->email ?? ($validated['email'] ?? null),
                'adresse' => $validated['address'].', '.$validated['city'].', '.$countryCode,
                'user_id' => null,
            ]);
            if ($portalAccount) {
                $portalAccount->customers()->syncWithoutDetaching([$customer->id]);
            }

            $order = new Order();
            $order->costumer_id = $customer->id;
            $order->subtotal = $subtotal;
            $order->tax = 0;
            $order->total = $subtotal;
            $order->montant = $subtotal;
            $order->paid_amount = 0;
            $order->code = 'WEB-'.Str::upper(Str::random(10));
            $order->time = 'À confirmer';
            $order->date_order = now()->toDateString();
            $order->type = 'PU';
            // Public partner orders are managed in the dedicated partner flow;
            // keep them out of the internal draft/order queues.
            $order->brouillon = 0;
            $order->status = 'ordered';
            $order->status_order = false;
            $order->take = false;
            $order->user_id = null;
            $order->created_user = null;
            $order->order_by = null;
            $order->remis = 0;
            $order->country_code = $countryCode;
            $order->partner_id = $partner->id;
            $order->public_order_link_id = $link?->id;
            $order->payment_timing = $validated['payment_timing'];
            $order->requested_payment_method = $validated['payment_method'];
            $order->public_tracking_token = $trackingToken;
            $order->partner_order_status = 'awaiting_delivery_quote';
            $order->save();

            foreach ($quantities as $productId => $item) {
                OrderItem::create([
                    'product_id' => (int) $productId,
                    'order_id' => $order->id,
                    'price' => (int) $prices[$productId]->price,
                    'quantity' => (int) $item['quantity'],
                    'high_price' => 0,
                ]);
            }

            return $order;
        });

        return redirect()->route('public-order.track', $order->public_tracking_token)
            ->with('success', 'La demande est envoyée au partenaire. Il va confirmer les frais de livraison avant la préparation.');
    }

    public function track(string $trackingToken): View
    {
        $order = Order::query()
            ->with(['orderItems.product', 'partner:id,name,phone', 'payments', 'costumer:id,phone,name'])
            ->where('public_tracking_token', $trackingToken)
            ->firstOrFail();

        return view('public-orders.track', compact('order'));
    }

    public function confirmQuote(string $trackingToken): RedirectResponse
    {
        $order = Order::query()->where('public_tracking_token', $trackingToken)->firstOrFail();
        DB::transaction(function () use ($order): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->partner_order_status === 'awaiting_customer_confirmation', 409);
            $locked->partner_order_status = $locked->payment_timing === 'before_delivery' ? 'awaiting_prepayment' : 'preparing';
            $locked->save();
        });

        return back()->with('success', $order->payment_timing === 'before_delivery'
            ? 'Le total est accepté. Tu peux maintenant enregistrer le paiement anticipé.'
            : 'Le total est accepté. Le partenaire prépare ta commande.');
    }

    public function submitPrepayment(Request $request, string $trackingToken): RedirectResponse
    {
        $order = Order::query()->where('public_tracking_token', $trackingToken)->firstOrFail();
        $validated = $request->validate([
            'transaction_reference' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($order, $validated): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->partner_order_status === 'awaiting_prepayment' && $locked->payment_timing === 'before_delivery', 409);
            abort_if($locked->payments()->where('stage', 'before_delivery')->whereIn('status', ['awaiting_partner_confirmation', 'confirmed'])->exists(), 409);

            $locked->payments()->create([
                'stage' => 'before_delivery',
                'method' => $locked->requested_payment_method,
                'amount' => max(0, (int) $locked->montant - (int) $locked->paid_amount),
                'status' => 'awaiting_partner_confirmation',
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Le paiement est transmis au partenaire pour confirmation.');
    }

    private function availableLink(string $token): PublicOrderLink
    {
        $link = PublicOrderLink::query()->where('token', $token)->firstOrFail();
        abort_unless($link->isAvailable(), 404);

        return $link;
    }

    private function portalCustomer(Request $request): ?Costumer
    {
        $accountId = $request->session()->get('customer_portal_account_id');
        if (! $accountId) {
            return null;
        }

        $account = CustomerPortalAccount::find($accountId);

        return $account?->customers()->orderByDesc('costumers.id')->first();
    }

    private function portalAddress(?Costumer $customer): string
    {
        $address = trim((string) ($customer?->adresse ?? ''));
        if ($address === '') {
            return '';
        }

        foreach (WestAfricaShippingCatalog::countries() as $code => $country) {
            foreach ($country['cities'] as $city) {
                $suffix = ', '.$city.', '.$code;
                if (str_ends_with(mb_strtolower($address), mb_strtolower($suffix))) {
                    return trim(substr($address, 0, -strlen($suffix)));
                }
            }
        }

        return $address;
    }
}
