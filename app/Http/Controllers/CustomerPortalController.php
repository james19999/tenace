<?php

namespace App\Http\Controllers;

use App\Models\CustomerLoyaltyProfile;
use App\Models\CustomerPortalAccount;
use App\Models\Costumer;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Support\CustomerPortalPhone;
use App\Support\WestAfricaShippingCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class CustomerPortalController extends Controller
{
    private const REMEMBER_COOKIE = 'tenacos_customer_portal_account';

    public function index(Request $request): View
    {
        $account = $this->currentAccount($request);
        if (! $account) {
            return view('customer-portal.index', [
                'account' => null,
                'countries' => WestAfricaShippingCatalog::countries(),
                'dialCodes' => CustomerPortalPhone::dialCodes(),
            ]);
        }

        $customerIds = $account->customers()->pluck('costumers.id');
        $profiles = $account->profiles()->orderBy('name')->orderBy('id')->get();
        $orders = Order::query()
            ->with(['orderItems.product', 'partner:id,name'])
            ->whereIn('costumer_id', $customerIds)
            ->latest('created_at')
            ->paginate(8);
        $recentProducts = OrderItem::query()
            ->with(['product', 'order:id,created_at'])
            ->whereHas('order', fn ($query) => $query->whereIn('costumer_id', $customerIds))
            ->latest('id')
            ->limit(10)
            ->get()
            ->unique('product_id')
            ->values();
        $profile = CustomerLoyaltyProfile::query()
            ->whereIn('costumer_id', $customerIds)
            ->latest('calculated_at')
            ->first();
        $deliveredCount = Order::query()->whereIn('costumer_id', $customerIds)
            ->where(fn ($query) => $query->where('status', 'delivered')->orWhere('partner_order_status', 'delivered'))
            ->count();

        return view('customer-portal.index', [
            'account' => $account,
            'profiles' => $profiles,
            'countries' => WestAfricaShippingCatalog::countries(),
            'orders' => $orders,
            'recentProducts' => $recentProducts,
            'profile' => $profile,
            'deliveredCount' => $deliveredCount,
            'customerName' => $profiles->first()?->name ?? $account->customers()->orderBy('costumers.id')->value('name'),
            'statusLabels' => [
                'awaiting_delivery_quote' => 'Frais de livraison à confirmer',
                'awaiting_customer_confirmation' => 'En attente de votre accord',
                'awaiting_prepayment' => 'En attente du paiement avant livraison',
                'preparing' => 'Commande en préparation',
                'ready_for_courier' => 'Prête, en attente d’un livreur',
                'assigned_to_courier' => 'Un livreur a été affecté',
                'handed_to_courier' => 'Le colis a été remis au livreur',
                'out_for_delivery' => 'Le livreur est en route',
                'delivered' => 'Livrée',
                'canceled' => 'Annulée',
            ],
            'categoryLabels' => [
                'new' => 'Nouvelle cliente', 'occasional' => 'Occasionnelle', 'regular' => 'Régulière',
                'faithful' => 'Fidèle', 'vip' => 'VIP', 'inactive' => 'Inactive', 'no_purchase' => 'Nouveau client',
            ],
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'country_code' => ['required', 'string', 'size:2', 'in:'.implode(',', array_keys(WestAfricaShippingCatalog::countries()))],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $key = 'customer-portal-login:'.sha1($request->ip().'|'.CustomerPortalPhone::toE164($validated['country_code'], $validated['phone']));
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return back()->withErrors(['phone' => 'Trop de tentatives. Réessaie dans quelques minutes.'])->onlyInput('phone', 'country_code');
        }

        $account = CustomerPortalAccount::query()
            ->where('phone_e164', CustomerPortalPhone::toE164($validated['country_code'], $validated['phone']))
            ->first();

        if (! $account || ! Hash::check($validated['password'], $account->password)) {
            RateLimiter::hit($key, 600);
            return back()->withErrors(['phone' => 'Numéro ou mot de passe incorrect.'])->onlyInput('phone', 'country_code');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('customer_portal_account_id', $account->id);
        $this->rememberAccount($account);

        $pendingCustomerId = $request->session()->get('customer_portal_pending_customer_id');
        $pendingPhone = $request->session()->get('customer_portal_pending_phone');
        if ($pendingCustomerId && $pendingPhone && hash_equals($account->phone_e164, $pendingPhone)) {
            $this->attachCustomerProfile($account, (int) $pendingCustomerId);
            $request->session()->forget(['customer_portal_pending_customer_id', 'customer_portal_pending_phone']);
        }

        return redirect()->route('customer-portal.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'customer_portal_account_id',
            'customer_portal_pending_customer_id',
            'customer_portal_pending_phone',
        ]);
        $request->session()->regenerateToken();
        Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE));

        return $request->input('destination') === 'catalog'
            ? redirect()->route('public-order.app')
            : redirect()->route('customer-portal.index');
    }

    public function storeProfile(Request $request): RedirectResponse
    {
        $account = $this->currentAccount($request);
        abort_unless($account, 403, 'Connecte-toi à ton espace client pour ajouter un profil.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:160'],
            'address' => ['nullable', 'string', 'max:180'],
        ]);

        $account->profiles()->create($validated);

        return redirect()->route('customer-portal.index')->with('success', 'Le profil de commande a été ajouté à ton espace.');
    }

    public function activate(Request $request, string $trackingToken): View|RedirectResponse
    {
        $order = $this->trackedOrder($trackingToken);
        $account = $this->currentAccount($request);
        $phone = CustomerPortalPhone::toE164((string) $order->country_code, (string) $order->costumer?->phone);

        if ($account) {
            abort_unless(hash_equals($account->phone_e164, $phone), 403);
            $this->attachCustomerProfile($account, (int) $order->costumer_id);

            return redirect()->route('customer-portal.index')->with('success', 'Cette commande est maintenant rattachée à ton espace client.');
        }

        $accountExists = CustomerPortalAccount::query()->where('phone_e164', $phone)->exists();
        if ($accountExists) {
            $request->session()->put('customer_portal_pending_customer_id', $order->costumer_id);
            $request->session()->put('customer_portal_pending_phone', $phone);
        }

        return view('customer-portal.activate', [
            'order' => $order,
            'accountExists' => $accountExists,
        ]);
    }

    public function storeAccount(Request $request, string $trackingToken): RedirectResponse
    {
        $order = $this->trackedOrder($trackingToken);
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
        ]);
        $phone = CustomerPortalPhone::toE164((string) $order->country_code, (string) $order->costumer?->phone);

        $account = CustomerPortalAccount::firstOrNew(['phone_e164' => $phone]);
        $account->password = Hash::make($validated['password']);
        $account->save();
        $this->attachCustomerProfile($account, (int) $order->costumer_id);
        $request->session()->regenerate();
        $request->session()->put('customer_portal_account_id', $account->id);
        $this->rememberAccount($account);
        $request->session()->forget(['customer_portal_pending_customer_id', 'customer_portal_pending_phone']);

        return redirect()->route('customer-portal.index')->with('success', 'Ton espace client est créé. Tu peux maintenant retrouver tes commandes ici.');
    }

    private function currentAccount(Request $request): ?CustomerPortalAccount
    {
        $accountId = $request->session()->get('customer_portal_account_id');
        $account = $accountId ? CustomerPortalAccount::with('customers')->find($accountId) : null;

        if (! $account) {
            $rememberedAccountId = $request->cookie(self::REMEMBER_COOKIE);
            $account = $rememberedAccountId
                ? CustomerPortalAccount::with('customers')->find($rememberedAccountId)
                : null;

            if ($account) {
                $request->session()->put('customer_portal_account_id', $account->id);
            } elseif ($rememberedAccountId) {
                Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE));
            }
        }

        return $account;
    }

    private function rememberAccount(CustomerPortalAccount $account): void
    {
        Cookie::queue(Cookie::forever(self::REMEMBER_COOKIE, (string) $account->id));
    }

    private function attachCustomerProfile(CustomerPortalAccount $account, int $customerId): void
    {
        $account->customers()->syncWithoutDetaching([$customerId]);
        $customer = Costumer::find($customerId);
        if (! $customer) {
            return;
        }

        $identity = [
            'name' => $customer->name ?: 'Client',
            'phone' => $customer->phone,
            'email' => $customer->email,
            'address' => $customer->adresse,
        ];
        $existingProfile = $account->profiles()
            ->where('name', $identity['name'])
            ->where('phone', $identity['phone'])
            ->where('email', $identity['email'])
            ->first();

        if (! $existingProfile) {
            $account->profiles()->firstOrCreate(['source_costumer_id' => $customer->id], $identity);
        }
    }

    private function trackedOrder(string $trackingToken): Order
    {
        return Order::query()
            ->with('costumer')
            ->where('public_tracking_token', $trackingToken)
            ->firstOrFail();
    }
}
