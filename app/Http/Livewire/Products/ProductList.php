<?php

namespace App\Http\Livewire\Products;

use App\Models\Product;
use App\Models\ProductCountryPrice;
use App\Models\PartnerProductStock;
use App\Models\QuantityPromotionSetting;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Gloudemans\Shoppingcart\Facades\Cart;
use App\Services\StockModuleSettingsService;
use App\Services\ShopSalesLocationResolver;
use App\Support\WestAfricaShippingCatalog;

class ProductList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $cartMessage = '';
    public $cartMessageIsError = false;
    public $search = '';
    public $stockFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->stockFilter = 'all';
        $this->resetPage();
    }

    public function AddToCart($id): void
    {
        if (Auth::user()?->isWholesaleSalesRole()) {
            $this->addWholesaleQuantity((int) $id);
            return;
        }
        $product = Product::findOrFail($id);
        $price = $product->price;
        $isRetailShopSeller = in_array(Auth::user()?->user_type, [
            \App\Models\User::ROLE_MANAGER,
            \App\Models\User::ROLE_SECRETARY,
            \App\Models\User::ROLE_SALES,
            \App\Models\User::ROLE_CASHIER,
            \App\Models\User::ROLE_CALL_CENTER,
        ], true);
        if ($isRetailShopSeller) {
            $location = app(ShopSalesLocationResolver::class)->forUser(Auth::user(), false);
            $available = $location ? $this->shopQuantity((int) $product->id, (int) $location->id) : 0;
            $cart = Cart::instance('cart');
            $items = $cart->content()->filter(fn ($item) => (int) $item->id === (int) $product->id);
            $alreadyInCart = (int) $items->sum('qty');
            if ($available <= $alreadyInCart) {
                $this->cartMessage = $available < 1
                    ? 'Ce produit n’est pas disponible dans ta boutique.'
                    : 'Tout le stock disponible de ce produit est déjà dans ton panier.';
                $this->cartMessageIsError = true;
                return;
            }
        }
        if (Auth::user()?->isWholesaleSalesRole()) {
            $wholesaleLocationId = app(StockModuleSettingsService::class)->rules()['wholesale_sales_location_id'] ?? null;
            $available = $this->shopQuantity((int) $product->id, $wholesaleLocationId ? (int) $wholesaleLocationId : null);
            if ($available <= 0) {
                $this->cartMessage = 'Ce produit n’est pas disponible dans la boutique grossiste.';
                $this->cartMessageIsError = true;
                return;
            }
        }
        if (Auth::user()?->user_type === \App\Models\User::ROLE_PARTNER) {
            $partner = Auth::user();
            $countryCode = strtoupper((string) $partner->country_code);
            $countryPrice = ProductCountryPrice::query()->where('product_id', $product->id)
                ->where('country_code', $countryCode)->where('is_available', true)->first();
            if (! $countryPrice) {
                $this->cartMessage = 'Ce produit n’est pas disponible dans le catalogue de ton pays.';
                $this->cartMessageIsError = true;
                return;
            }

            $reserve = (int) PartnerProductStock::query()->where('partner_id', $partner->id)
                ->where('country_code', $countryCode)->where('product_id', $product->id)->value('quantity');
            $withCouriers = (int) DB::table('partner_courier_stock_issue_lines as lines')
                ->join('partner_courier_stock_issues as issues', 'issues.id', '=', 'lines.issue_id')
                ->where('issues.partner_id', $partner->id)->where('issues.country_code', $countryCode)
                ->where('lines.product_id', $product->id)->sum('lines.quantity_remaining');
            $available = $reserve + $withCouriers;
            $alreadyInCart = (int) Cart::instance('cart')->content()
                ->filter(fn ($item) => (int) $item->id === (int) $product->id)->sum('qty');
            if ($available <= $alreadyInCart) {
                $this->cartMessage = $available < 1
                    ? 'Stock épuisé pour ce produit. Demande un réapprovisionnement avant de l’ajouter.'
                    : 'La quantité disponible de ce produit est déjà dans ton panier.';
                $this->cartMessageIsError = true;
                return;
            }
            $price = $countryPrice->price;
        }
        if (Auth::user()?->user_type !== \App\Models\User::ROLE_PARTNER) {
            $cart = Cart::instance('cart');
            $existingItems = $cart->content()->filter(fn ($item) => (int) $item->id === (int) $product->id);
            $newQuantity = (int) $existingItems->sum('qty') + 1;
            foreach ($existingItems as $existingItem) $cart->remove($existingItem->rowId);
            $price = (int) $product->price;
            $cart->add($product->id, $product->name, $newQuantity, $price)->associate(Product::class);
            $this->cartUpdated();
            return;
        }
        Cart::instance('cart')->add($product->id, $product->name, 1, $price)->associate(Product::class);
        $this->cartUpdated();
    }

    public function addWholesaleQuantity(int $id): void
    {
        if (! Auth::check()) return;

        $product = Product::query()->with('quantityPriceTiers')->findOrFail($id);
        $minimum = (int) $product->quantityPriceTiers->min('min_quantity');
        $quantity = $minimum;
        if ($minimum < 1) {
            $this->cartMessage = $minimum > 0
                ? 'La quantité minimale pour « '.$product->name.' » est de '.$minimum.' unités.'
                : 'Aucun palier grossiste n’est configuré pour « '.$product->name.' ».';
            $this->cartMessageIsError = true;
            return;
        }

        $locationId = app(StockModuleSettingsService::class)->rules()['wholesale_sales_location_id'] ?? null;
        $available = $this->shopQuantity((int) $product->id, $locationId ? (int) $locationId : null);
        $cart = Cart::instance('cart');
        $existingItems = $cart->content()->filter(fn ($item) => (int) $item->id === (int) $product->id);
        $alreadyInCart = (int) $existingItems->sum('qty');
        $newTotal = $alreadyInCart + (int) $quantity;
        if ($newTotal > $available) {
            $remaining = max(0, $available - $alreadyInCart);
            $this->cartMessage = 'Il reste '.$remaining.' unité(s) disponibles pour « '.$product->name.' » dans la boutique grossiste.';
            $this->cartMessageIsError = true;
            return;
        }

        foreach ($existingItems as $item) $cart->remove($item->rowId);
        $cart->add($product->id, $product->name, $newTotal, (int) $product->price)->associate(Product::class);
        $this->cartUpdated();
    }

    private function shopQuantity(int $productId, ?int $locationId): int
    {
        if (! $locationId) return 0;
        return (int) DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->where('sm.status', 'posted')->where('sml.location_id', $locationId)
            ->where('sml.product_id', $productId)->sum('sml.quantity_delta');
    }

    private function cartUpdated(): void
    {
        $this->cartMessage = 'Produit ajouté au panier.';
        $this->cartMessageIsError = false;
        $count = (int) Cart::instance('cart')->count();
        $this->emit('cartUpdated');
        $this->dispatchBrowserEvent('cart-count-updated', ['count' => $count]);
    }

    public function render()
    {
        $user = Auth::user();
        $isPartner = $user?->user_type === \App\Models\User::ROLE_PARTNER;
        $isCommercialManager = $user?->isWholesaleSalesRole() ?? false;
        $isShopManager = $user?->isManager() ?? false;
        $usesRetailShopStock = in_array($user?->user_type, [
            \App\Models\User::ROLE_MANAGER,
            \App\Models\User::ROLE_SECRETARY,
            \App\Models\User::ROLE_SALES,
            \App\Models\User::ROLE_CASHIER,
            \App\Models\User::ROLE_CALL_CENTER,
        ], true);
        $partnerCountryCode = $isPartner ? strtoupper((string) $user->country_code) : null;
        $stockRules = app(StockModuleSettingsService::class)->rules();
        $salesLocationId = $stockRules['default_sales_location_id'] ?? null;
        $wholesaleLocationId = $stockRules['wholesale_sales_location_id'] ?? null;
        $sellerShop = app(ShopSalesLocationResolver::class)->forUser($user, false);
        $stockQuantityExpression = $usesRetailShopStock
            ? 'COALESCE(shop_stock.quantity, 0)'
            : ($isCommercialManager ? 'COALESCE(wholesale_shop_stock.quantity, 0)' : '(COALESCE(shop_stock.quantity, 0) + COALESCE(wholesale_shop_stock.quantity, 0))');
        $inventoryQuantityExpression = $stockQuantityExpression;
        $trackedRetailStockCondition = $usesRetailShopStock ? 'shop_stock.product_id IS NOT NULL AND ' : '';
        $totalProductsExpression = $usesRetailShopStock
            ? 'COUNT(DISTINCT CASE WHEN shop_stock.product_id IS NOT NULL THEN products.id END)'
            : 'COUNT(*)';
        $shopStock = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'sml.location_id')
            ->where('sm.status', 'posted')
            ->where('sl.type', 'shop')
            ->when($sellerShop, fn ($query) => $query->where('sl.id', $sellerShop->id), fn ($query) => $query->when($salesLocationId, fn ($scope) => $scope->where('sl.id', $salesLocationId)))
            ->select('sml.product_id')
            ->selectRaw('SUM(sml.quantity_delta) as quantity')
            ->groupBy('sml.product_id');
        $wholesaleShopStock = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->where('sm.status', 'posted')->where('sml.location_id', $isShopManager ? -1 : ($wholesaleLocationId ?: -1))
            ->select('sml.product_id')->selectRaw('SUM(sml.quantity_delta) as quantity')->groupBy('sml.product_id');

        $inventoryQuery = Product::query()
            ->leftJoinSub(clone $shopStock, 'shop_stock', 'shop_stock.product_id', '=', 'products.id')
            ->leftJoinSub(clone $wholesaleShopStock, 'wholesale_shop_stock', 'wholesale_shop_stock.product_id', '=', 'products.id')
            ->selectRaw($totalProductsExpression.' as total_products')
            ->selectRaw('SUM(CASE WHEN '.$trackedRetailStockCondition.$inventoryQuantityExpression.' <= 0 THEN 1 ELSE 0 END) as out_of_stock_count')
            ->selectRaw('SUM(CASE WHEN '.$trackedRetailStockCondition.$inventoryQuantityExpression.' > 0 AND qts_seuil IS NOT NULL AND '.$inventoryQuantityExpression.' <= qts_seuil THEN 1 ELSE 0 END) as low_stock_count')
            ->selectRaw('SUM(CASE WHEN '.$trackedRetailStockCondition.$inventoryQuantityExpression.' > 0 AND (qts_seuil IS NULL OR '.$inventoryQuantityExpression.' > qts_seuil) THEN 1 ELSE 0 END) as available_count')
            ->selectRaw('COALESCE(SUM(shop_stock.quantity), 0) as shop_stock_total')
            ->selectRaw('COALESCE(SUM(wholesale_shop_stock.quantity), 0) as wholesale_shop_stock_total');

        if (Auth::user()?->isAdmin()) {
            $inventoryQuery
                ->selectRaw('COALESCE(SUM(price_market * COALESCE(shop_stock.quantity, 0)), 0) as purchase_stock_value')
                ->selectRaw('COALESCE(SUM(price * COALESCE(shop_stock.quantity, 0)), 0) as retail_stock_value');
        }

        $inventory = $inventoryQuery->first();

        $productsQuery = Product::query()
            ->with(['quantityPriceTiers', 'countryPrices' => fn ($query) => $query
                ->when($isPartner, fn ($prices) => $prices->where('country_code', $partnerCountryCode)->where('is_available', true))
                ->orderBy('country_code')])
            ->leftJoinSub(clone $shopStock, 'shop_stock', 'shop_stock.product_id', '=', 'products.id')
            ->leftJoinSub(clone $wholesaleShopStock, 'wholesale_shop_stock', 'wholesale_shop_stock.product_id', '=', 'products.id')
            ->select('products.*')
            ->selectRaw('COALESCE(shop_stock.quantity, 0) as shop_quantity')
            ->selectRaw('COALESCE(wholesale_shop_stock.quantity, 0) as wholesale_shop_quantity')
            ->when($isPartner, fn ($query) => $query->whereHas('countryPrices', fn ($prices) => $prices
                ->where('country_code', $partnerCountryCode)->where('is_available', true)))
            ->when($usesRetailShopStock, fn ($query) => $query->whereRaw('COALESCE(shop_stock.quantity, 0) > 0'))
            ->when($isCommercialManager, fn ($query) => $query->whereRaw('COALESCE(wholesale_shop_stock.quantity, 0) > 0'))
            ->when(trim($this->search) !== '', function ($query) {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($search) use ($term) {
                    $search->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($this->stockFilter === 'out', fn ($query) => $query->whereRaw($stockQuantityExpression.' <= 0'))
            ->when($this->stockFilter === 'low', fn ($query) => $query->whereRaw($stockQuantityExpression.' > 0')
                ->whereNotNull('products.qts_seuil')->whereRaw($stockQuantityExpression.' <= products.qts_seuil'))
            ->when($this->stockFilter === 'available', fn ($query) => $query->whereRaw($stockQuantityExpression.' > 0')
                ->where(function ($stock) use ($stockQuantityExpression) {
                    $stock->whereNull('products.qts_seuil')->orWhereRaw($stockQuantityExpression.' > products.qts_seuil');
                }));

        $Products = $productsQuery->orderBy('name')->paginate(12);
        $promotionSetting = QuantityPromotionSetting::current();
        $wholesaleCartQuantities = Cart::instance('cart')->content()
            ->groupBy(fn ($item) => (int) $item->id)
            ->map(fn ($items) => (int) $items->sum('qty'));
        $promotedProductIds = $Products->getCollection()
            ->filter(fn ($product) => $promotionSetting->hasPromotionForProduct((int) $product->id))
            ->modelKeys();
        $promotionPrices = $isPartner ? collect() : $Products->getCollection()->mapWithKeys(function ($product) use ($promotionSetting) {
            if (! $promotionSetting->hasPromotionForProduct((int) $product->id)) return [];
            $tiers = collect($promotionSetting->tiers ?? []);
            $productTiers = $tiers->filter(fn ($tier) => (int) ($tier['product_id'] ?? 0) === (int) $product->id);
            $eligibleTiers = $productTiers->isNotEmpty()
                ? $productTiers
                : $tiers->filter(fn ($tier) => empty($tier['product_id']));
            $firstTier = $eligibleTiers->filter(fn ($tier) => (int) ($tier['min_quantity'] ?? 0) > 0)
                ->sortBy(fn ($tier) => (int) $tier['min_quantity'])->first();
            if (! $firstTier) return [];
            $quantity = (int) $firstTier['min_quantity'];
            $regularPrice = (int) $product->unitPriceForQuantity($quantity);
            $pricing = $promotionSetting->priceAfterPromotionForProductQuantity((int) $product->id, $quantity, $regularPrice);
            if (! $pricing) return [];
            return [(int) $product->id => [
                'quantity' => $quantity,
                'regular_price' => $pricing['regular_price'],
                'promo_price' => $pricing['unit_price'],
                'discount_label' => $pricing['discount_type'] === 'percent'
                    ? '−'.$pricing['discount_value'].' % appliqués'
                    : 'Prix fixe · '.number_format($pricing['unit_price'], 0, ',', ' ').' F / unité',
            ]];
        });
        $partnerStockSummary = collect();
        $partnerCartQuantities = collect();
        if ($isPartner) {
            $productIds = $Products->getCollection()->modelKeys();
            $reserveStocks = PartnerProductStock::query()
                ->where('partner_id', $user->id)
                ->where('country_code', $partnerCountryCode)
                ->whereIn('product_id', $productIds)
                ->pluck('quantity', 'product_id');
            $courierStocks = DB::table('partner_courier_stock_issue_lines as lines')
                ->join('partner_courier_stock_issues as issues', 'issues.id', '=', 'lines.issue_id')
                ->where('issues.partner_id', $user->id)
                ->where('issues.country_code', $partnerCountryCode)
                ->whereIn('lines.product_id', $productIds)
                ->groupBy('lines.product_id')
                ->selectRaw('lines.product_id, SUM(lines.quantity_remaining) as quantity')
                ->pluck('quantity', 'product_id');

            foreach ($productIds as $productId) {
                $reserve = (int) ($reserveStocks[$productId] ?? 0);
                $withCouriers = (int) ($courierStocks[$productId] ?? 0);
                $partnerStockSummary[$productId] = [
                    'reserve' => $reserve,
                    'couriers' => $withCouriers,
                    'available' => $reserve + $withCouriers,
                ];
            }
            $partnerCartQuantities = Cart::instance('cart')->content()
                ->groupBy(fn ($item) => (int) $item->id)
                ->map(fn ($items) => (int) $items->sum('qty'));
        }

        return view('livewire.products.product-list', [
            'Products' => $Products,
            'promotedProductIds' => $promotedProductIds,
            'promotionPrices' => $promotionPrices,
            'inventory' => $inventory,
            'countryNames' => collect(WestAfricaShippingCatalog::countries())->map(fn ($country) => $country['label']),
            'isPartner' => $isPartner,
            'isShopManager' => $isShopManager,
            'usesRetailShopStock' => $usesRetailShopStock,
            'shopManagerLocationLabel' => $sellerShop?->name ?? 'aucune boutique assignée',
            'isCommercialManager' => $isCommercialManager,
            'wholesaleCartQuantities' => $wholesaleCartQuantities,
            'partnerCountryCode' => $partnerCountryCode,
            'partnerStockSummary' => $partnerStockSummary,
            'partnerCartQuantities' => $partnerCartQuantities,
            'hasWholesaleLocation' => (bool) $wholesaleLocationId,
        ])
            ->extends('layouts.admin')
            ->section('content');
    }
}
