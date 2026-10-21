<?php

namespace App\Http\Controllers;

use App\Models\ProductCountryPrice;
use App\Models\PublicOrderLink;
use App\Models\User;
use App\Models\Costumer;
use App\Models\CustomerPortalAccount;
use App\Models\CustomerPortalProfile;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Support\WestAfricaShippingCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\OrderPayment;
use App\Support\CustomerPortalPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicOrderController extends Controller
{
    private const CUSTOMER_PORTAL_COOKIE = 'tenacos_customer_portal_account';

    public function app(Request $request): View
    {
        $portalAccount = $this->portalAccount($request);
        $savedCustomer = $portalAccount?->customers()->orderByDesc('costumers.id')->first();
        $savedProfiles = $this->portalProfiles($request);
        $countries = WestAfricaShippingCatalog::countries();
        $initialCountry = strtoupper((string) $request->query('country', ''));
        if (! isset($countries[$initialCountry])) {
            $initialCountry = '';
        }

        return view('public-orders.show', [
            'link' => null,
            'countries' => $countries,
            'initialCountry' => $initialCountry,
            'hasPortalAccount' => $portalAccount !== null,
            'savedCustomer' => $savedCustomer,
            'savedProfiles' => $savedProfiles,
            'savedAddress' => $savedProfiles->first()?->checkout_address ?? $this->portalAddress($savedCustomer),
        ]);
    }

    public function show(Request $request, string $token): View
    {
        $link = $this->availableLink($token);
        $this->recordLinkVisit($request, $link);
        $portalAccount = $this->portalAccount($request);
        $savedCustomer = $portalAccount?->customers()->orderByDesc('costumers.id')->first();
        $savedProfiles = $this->portalProfiles($request);

        return view('public-orders.show', [
            'link' => $link,
            'countries' => WestAfricaShippingCatalog::countries(),
            'initialCountry' => strtoupper((string) $link->country_code),
            'hasPortalAccount' => $portalAccount !== null,
            'savedCustomer' => $savedCustomer,
            'savedProfiles' => $savedProfiles,
            'savedAddress' => $savedProfiles->first()?->checkout_address ?? $this->portalAddress($savedCustomer),
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
        $countryCode = strtoupper((string) ($link?->country_code ?: $request->query('country', '')));
        if ($link?->country_code) {
            abort_unless($countryCode === strtoupper($link->country_code), 404);
        }
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
            ], 422);
        }

        $featuredIds = $link
            ? $link->products()->pluck('products.id')->map(fn ($id) => (int) $id)->all()
            : [];
        $prices = ProductCountryPrice::query()
            ->with(['product:id,name,description,img', 'quantityTiers'])
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
                'quantity_prices' => $price->quantityTiers->map(fn ($tier) => [
                    'min_quantity' => (int) $tier->min_quantity,
                    'unit_price' => (int) $tier->unit_price,
                ])->values(),
                'featured' => in_array((int) $price->product_id, $featuredIds, true),
            ]);

        return response()->json([
            'country' => ['code' => $countryCode, 'name' => $countries[$countryCode]['label']],
            'products' => $prices,
        ])->header('Cache-Control', 'no-store');
    }

    public function placeOrder(Request $request, ?string $token = null): RedirectResponse
    {
        $link = $token !== null ? $this->availableLink($token) : null;
        $countries = WestAfricaShippingCatalog::countries();
        $countryCode = strtoupper((string) $request->input('country_code'));
        $portalAccount = $this->portalAccount($request);
        $savedProfiles = $portalAccount?->profiles()->orderBy('name')->orderBy('id')->get() ?? collect();
        $portalCustomer = $portalAccount?->customers()->orderByDesc('costumers.id')->first();
        $customerDetailsRequired = $savedProfiles->isEmpty() && ! $portalCustomer;

        $rules = [
            'country_code' => ['required', Rule::in($link?->country_code ? [strtoupper($link->country_code)] : array_keys($countries))],
            'name' => [$customerDetailsRequired ? 'required' : 'nullable', 'string', 'max:160'],
            'phone' => [$customerDetailsRequired ? 'required' : 'nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:180'],
            'payment_timing' => ['nullable', Rule::in(['before_delivery', 'on_delivery'])],
            'payment_method' => ['nullable', Rule::in(['cash', 'mobile_money'])],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ];
        if ($savedProfiles->isNotEmpty()) {
            $rules['customer_profile_id'] = [
                'required',
                'integer',
                Rule::exists('customer_portal_profiles', 'id')->where('customer_portal_account_id', $portalAccount->id),
            ];
        }

        $validated = $request->validate($rules, [
            'items.required' => 'Ajoute au moins un produit à ta commande.',
        ]);
        $countryCode = strtoupper($validated['country_code']);
        $selectedProfile = ! empty($validated['customer_profile_id'])
            ? $savedProfiles->firstWhere('id', (int) $validated['customer_profile_id'])
            : null;
        $orderCustomer = $selectedProfile ?? $portalCustomer;

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
            ->with(['product', 'quantityTiers'])
            ->where('country_code', $countryCode)
            ->where('is_available', true)
            ->whereIn('product_id', $quantities->keys())
            ->get()
            ->keyBy('product_id');

        if ($prices->count() !== $quantities->count()) {
            return back()->withErrors(['items' => 'Un produit sélectionné n’est plus disponible dans ce pays.'])->withInput();
        }

        $trackingToken = (string) Str::uuid();
        $order = DB::transaction(function () use ($validated, $countryCode, $partner, $link, $prices, $quantities, $trackingToken, $portalAccount, $orderCustomer): Order {
            $subtotal = 0;
            foreach ($quantities as $productId => $item) {
                $subtotal += $prices[$productId]->unitPriceForQuantity((int) $item['quantity']) * (int) $item['quantity'];
            }

            $customer = Costumer::create([
                'name' => $orderCustomer?->name ?? ($validated['name'] ?? ''),
                'phone' => $orderCustomer?->phone ?? trim((string) ($validated['phone'] ?? '')),
                'email' => $orderCustomer?->email,
                'adresse' => $validated['address'],
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
            $order->delivery_fee_mode = 'per_order';
            $order->delivery_mode = 'local';
            $order->shipping_country = $countryCode;
            $order->shipping_city = null;
            $order->partner_id = $partner->id;
            $order->public_order_link_id = $link?->id;
            $order->payment_timing = $validated['payment_timing'] ?? 'on_delivery';
            $order->requested_payment_method = $validated['payment_method'] ?? 'cash';
            $order->public_tracking_token = $trackingToken;
            $order->partner_order_status = 'awaiting_delivery_quote';
            $order->save();

            foreach ($quantities as $productId => $item) {
                OrderItem::create([
                    'product_id' => (int) $productId,
                    'order_id' => $order->id,
                    'price' => $prices[$productId]->unitPriceForQuantity((int) $item['quantity']),
                    'quantity' => (int) $item['quantity'],
                    'high_price' => 0,
                    'delivery_fee' => 0,
                    'delivery_fee_is_override' => false,
                ]);
            }

            return $order;
        });

        return redirect()->route('public-order.track', $order->public_tracking_token)
            ->with('success', 'Ta commande et son montant total ont été envoyés au partenaire.');
    }

    public function track(string $trackingToken): View
    {
        $order = Order::query()
            ->with(['orderItems.product', 'partner:id,name,phone', 'payments', 'costumer:id,phone,name'])
            ->where('public_tracking_token', $trackingToken)
            ->firstOrFail();

        $hasCustomerPortalAccount = false;
        if ($order->costumer?->phone) {
            try {
                $phone = CustomerPortalPhone::toE164((string) $order->country_code, (string) $order->costumer->phone);
                $hasCustomerPortalAccount = CustomerPortalAccount::query()->where('phone_e164', $phone)->exists();
            } catch (ValidationException) {
                // Les anciennes commandes peuvent contenir un téléphone non normalisable.
            }
        }

        return view('public-orders.track', compact('order', 'hasCustomerPortalAccount'));
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

    private function recordLinkVisit(Request $request, PublicOrderLink $link): void
    {
        $now = now();
        $visitorHash = hash_hmac('sha256', $request->session()->getId(), (string) config('app.key'));
        $visits = DB::table('public_order_link_visits');
        $visits->insertOrIgnore([
            'public_order_link_id' => $link->id,
            'visitor_hash' => $visitorHash,
            'visits' => 0,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $visits->where('public_order_link_id', $link->id)
            ->where('visitor_hash', $visitorHash)
            ->increment('visits', 1, ['last_seen_at' => $now, 'updated_at' => $now]);
    }

    private function portalAccount(Request $request): ?CustomerPortalAccount
    {
        $accountId = $request->session()->get('customer_portal_account_id');
        $account = $accountId ? CustomerPortalAccount::find($accountId) : null;
        if (! $account) {
            $rememberedAccountId = $request->cookie(self::CUSTOMER_PORTAL_COOKIE);
            $account = $rememberedAccountId ? CustomerPortalAccount::find($rememberedAccountId) : null;
        }
        if ($account) {
            $request->session()->put('customer_portal_account_id', $account->id);
        }

        return $account;
    }

    private function portalProfiles(Request $request)
    {
        $account = $this->portalAccount($request);
        if (! $account) {
            return collect();
        }

        return CustomerPortalProfile::query()
            ->where('customer_portal_account_id', $account->id)
            ->orderBy('name')->orderBy('id')->get()
            ->each(function (CustomerPortalProfile $profile): void {
                $profile->setAttribute('checkout_address', $this->portalAddressFromString($profile->address));
            });
    }

    private function portalAddress(?Costumer $customer): string
    {
        return $this->portalAddressFromString((string) ($customer?->adresse ?? ''));
    }

    private function portalAddressFromString(?string $value): string
    {
        $address = trim((string) $value);
        if ($address === '') {
            return '';
        }

        foreach (array_keys(WestAfricaShippingCatalog::countries()) as $code) {
            $suffix = ', '.$code;
            if (str_ends_with(mb_strtolower($address), mb_strtolower($suffix))) {
                return trim(substr($address, 0, -strlen($suffix)));
            }
        }

        return $address;
    }
}
