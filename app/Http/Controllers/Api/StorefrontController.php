<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Costumer;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockLocation;
use App\Models\User;
use App\Notifications\StorefrontOrderNotification;
use App\Services\StockModuleSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorefrontController extends Controller
{
    public function catalog(Request $request): JsonResponse
    {
        $this->authorizeStorefront($request);
        $location = $this->salesLocation();
        $balances = $this->stockBalances((int) $location->id);

        $soldQuantities = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->join('orders as o', 'o.id', '=', 'sm.order_id')
            ->where('sm.type', 'sale')
            ->where('sm.status', 'posted')
            ->where('sml.location_id', (int) $location->id)
            ->where('o.status', 'delivered')
            ->where(function ($query) {
                $query->whereNull('o.is_wholesale')->orWhere('o.is_wholesale', false);
            })
            ->where('sml.quantity_delta', '<', 0)
            ->select('sml.product_id')
            ->selectRaw('SUM(ABS(sml.quantity_delta)) as units_sold')
            ->groupBy('sml.product_id')
            ->pluck('units_sold', 'product_id');

        $products = Product::query()
            ->whereIn('id', $balances->filter(fn ($quantity) => (float) $quantity > 0)->keys())
            ->whereRaw('UPPER(name) NOT LIKE ?', ['DEMO%'])
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'img', 'price'])
            ->map(fn (Product $product) => [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'description' => (string) ($product->description ?? ''),
                'image' => $product->img ? url('image/'.$product->img) : null,
                'price' => (int) round((float) $product->price),
                'available_quantity' => max(0, (int) ($balances[$product->id] ?? 0)),
                'units_sold' => (int) ($soldQuantities[$product->id] ?? 0),
            ])
            ->sort(function (array $left, array $right) {
                return ($right['units_sold'] <=> $left['units_sold']) ?: strcasecmp($left['name'], $right['name']);
            })
            ->values()
            ->map(function (array $product, int $index) {
                $product['best_seller'] = $index < 6 && $product['units_sold'] > 0;
                unset($product['units_sold']);

                return $product;
            });

        return response()->json([
            'currency' => 'XOF',
            'brand' => $this->storefrontBrand(),
            'location' => ['id' => (int) $location->id, 'name' => $location->name],
            'products' => $products,
        ])->header('Cache-Control', 'no-store');
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $this->authorizeStorefront($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[+0-9 ()-]+$/'],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $city = trim($validated['city']);

        $order = DB::transaction(function () use ($validated, $city): Order {
            $location = $this->salesLocation();
            $balances = $this->stockBalances((int) $location->id);
            $items = collect($validated['items'])->keyBy(fn (array $item) => (int) $item['product_id']);
            if ($items->count() !== count($validated['items'])) {
                throw ValidationException::withMessages(['items' => 'Un produit ne peut apparaître qu’une seule fois dans le panier.']);
            }

            $products = Product::query()->whereIn('id', $items->keys())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $subtotal = 0;
            foreach ($items as $productId => $item) {
                $product = $products->get($productId);
                $quantity = (int) $item['quantity'];
                if (! $product || (int) ($balances[$productId] ?? 0) < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Le stock vient de changer. Actualise le panier avant de confirmer la commande.',
                    ]);
                }
                $subtotal += (int) round((float) $product->price) * $quantity;
            }

            $phone = preg_replace('/\s+/', '', trim($validated['phone']));
            $customer = Costumer::query()->create([
                'name' => trim($validated['name']),
                'phone' => $phone,
                'email' => null,
                'adresse' => $city.' · '.trim($validated['address']),
                'user_id' => null,
            ]);

            $order = new Order();
            $order->costumer_id = $customer->id;
            $order->subtotal = $subtotal;
            $order->tax = 0;
            $order->total = $subtotal;
            $order->montant = $subtotal;
            $order->paid_amount = 0;
            $order->code = 'WEB-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
            $order->time = 'À confirmer';
            $order->date_order = today()->toDateString();
            $order->type = 'PU';
            $order->brouillon = 1;
            $order->status = 'ordered';
            $order->status_order = false;
            $order->take = false;
            $order->user_id = null;
            $order->created_user = null;
            $order->order_by = null;
            $order->remis = 0;
            $order->is_wholesale = false;
            $order->delivery_mode = 'local';
            $order->delivery_fee_mode = 'per_order';
            $order->delivery_address = $city.' · '.trim($validated['address']);
            $order->delivery_phone = $phone;
            $order->shipping_country = 'TG';
            $order->shipping_city = $city;
            $order->country_code = 'TG';
            $order->payment_timing = 'on_delivery';
            $order->requested_payment_method = 'cash';
            $order->public_tracking_token = (string) Str::uuid();
            $order->save();

            foreach ($items as $productId => $item) {
                $product = $products->get($productId);
                OrderItem::query()->create([
                    'product_id' => (int) $productId,
                    'order_id' => $order->id,
                    'price' => (int) round((float) $product->price),
                    'quantity' => (int) $item['quantity'],
                    'high_price' => 0,
                    'delivery_fee' => 0,
                    'delivery_fee_is_override' => false,
                ]);
            }

            return $order;
        });

        User::query()
            ->where('active', true)
            ->whereIn('user_type', [
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
                User::ROLE_SECRETARY,
                User::ROLE_SALES,
                User::ROLE_CASHIER,
                User::ROLE_CALL_CENTER,
            ])
            ->each(fn (User $user) => $user->notify(new StorefrontOrderNotification($order)));

        return response()->json([
            'order' => [
                'id' => (int) $order->id,
                'code' => $order->code,
                'tracking_token' => $order->public_tracking_token,
                'status' => $order->status,
                'items_total' => (int) $order->total,
                'delivery_fee' => 0,
                'amount_due' => (int) $order->montant,
                'payment' => 'cash_on_delivery',
            ],
            'message' => 'Commande enregistrée. L’équipe confirmera les frais de livraison avant l’expédition.',
        ], 201)->header('Cache-Control', 'no-store');
    }

    public function track(Request $request, string $token): JsonResponse
    {
        $this->authorizeStorefront($request);
        $order = Order::query()->with('orderItems.product:id,name')
            ->where('public_tracking_token', $token)->firstOrFail();

        return response()->json([
            'brand' => $this->storefrontBrand(),
            'code' => $order->code,
            'status' => $order->status,
            'items_total' => (int) $order->total,
            'delivery_fee' => (int) $order->tax,
            'amount_due' => (int) ($order->montant ?: ((int) $order->total + (int) $order->tax)),
            'payment' => 'cash_on_delivery',
            'items' => $order->orderItems->map(fn (OrderItem $item) => [
                'name' => (string) ($item->product?->name ?? 'Produit'),
                'quantity' => (int) $item->quantity,
                'unit_price' => (int) $item->price,
            ])->values(),
        ])->header('Cache-Control', 'no-store');
    }

    private function authorizeStorefront(Request $request): void
    {
        $expected = (string) config('services.storefront.api_key');
        abort_if($expected === '' || ! hash_equals($expected, (string) $request->bearerToken()), 401);
    }

    private function salesLocation(): StockLocation
    {
        $locationId = app(StockModuleSettingsService::class)->rules()['default_sales_location_id'] ?? null;
        $location = $locationId
            ? StockLocation::query()->whereKey($locationId)->where('type', 'shop')->where('is_active', true)->first()
            : null;

        abort_unless($location, 503, 'La boutique centrale n’est pas configurée pour les ventes en ligne.');

        return $location;
    }

    private function stockBalances(int $locationId)
    {
        $query = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->where('sm.status', 'posted')
            ->where('sml.location_id', $locationId);

        return $query->select('sml.product_id')->selectRaw('SUM(sml.quantity_delta) as quantity')
            ->groupBy('sml.product_id')->pluck('quantity', 'product_id');
    }

    private function storefrontBrand(): array
    {
        $setting = Setting::query()->first(['name', 'img']);
        $logo = trim((string) ($setting?->img ?? ''));

        return [
            'name' => (string) ($setting?->name ?: 'TENACOS'),
            'logo_url' => $logo !== '' && strtolower($logo) !== 'noimage.jpg'
                ? url('image/'.$logo)
                : null,
        ];
    }
}
