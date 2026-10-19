<?php

namespace App\Http\Livewire\Products;

use App\Models\Product;
use App\Models\ProductCountryPrice;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Gloudemans\Shoppingcart\Facades\Cart;
use App\Services\StockModuleSettingsService;
use App\Support\WestAfricaShippingCatalog;

class ProductList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $cartMessage = '';
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
        $product = Product::findOrFail($id);
        $price = $product->price;
        if (Auth::user()?->user_type === \App\Models\User::ROLE_PARTNER) {
            $countryPrice = ProductCountryPrice::query()->where('product_id', $product->id)
                ->where('country_code', Auth::user()->country_code)->where('is_available', true)->firstOrFail();
            $price = $countryPrice->price;
        }
        Cart::instance('cart')->add($product->id, $product->name, 1, $price)->associate(Product::class);
        $this->cartUpdated();
    }

    public function AddToCartHighPrice($id): void
    {
        abort_unless(Auth::user()?->user_type !== \App\Models\User::ROLE_PARTNER, 403);
        $product = Product::findOrFail($id);
        Cart::instance('cart')->add($product->id, $product->name, 1, $product->high_price)->associate(Product::class);
        $this->cartUpdated();
    }

    private function cartUpdated(): void
    {
        $this->cartMessage = 'Produit ajouté au panier.';
        $count = (int) Cart::instance('cart')->count();
        $this->emit('cartUpdated');
        $this->dispatchBrowserEvent('cart-count-updated', ['count' => $count]);
    }

    public function render()
    {
        $user = Auth::user();
        $isPartner = $user?->user_type === \App\Models\User::ROLE_PARTNER;
        $partnerCountryCode = $isPartner ? strtoupper((string) $user->country_code) : null;
        $salesLocationId = app(StockModuleSettingsService::class)->rules()['default_sales_location_id'] ?? null;
        $shopStock = DB::table('stock_movement_lines as sml')
            ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'sml.location_id')
            ->where('sm.status', 'posted')
            ->where('sl.type', 'shop')
            ->when($salesLocationId, fn ($query) => $query->where('sl.id', $salesLocationId))
            ->select('sml.product_id')
            ->selectRaw('SUM(sml.quantity_delta) as quantity')
            ->groupBy('sml.product_id');

        $inventoryQuery = Product::query()
            ->leftJoinSub(clone $shopStock, 'shop_stock', 'shop_stock.product_id', '=', 'products.id')
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw('SUM(CASE WHEN COALESCE(shop_stock.quantity, 0) <= 0 THEN 1 ELSE 0 END) as out_of_stock_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(shop_stock.quantity, 0) > 0 AND qts_seuil IS NOT NULL AND shop_stock.quantity <= qts_seuil THEN 1 ELSE 0 END) as low_stock_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(shop_stock.quantity, 0) > 0 AND (qts_seuil IS NULL OR shop_stock.quantity > qts_seuil) THEN 1 ELSE 0 END) as available_count')
            ->selectRaw('COALESCE(SUM(shop_stock.quantity), 0) as shop_stock_total');

        if (Auth::user()?->isAdmin()) {
            $inventoryQuery
                ->selectRaw('COALESCE(SUM(price_market * COALESCE(shop_stock.quantity, 0)), 0) as purchase_stock_value')
                ->selectRaw('COALESCE(SUM(price * COALESCE(shop_stock.quantity, 0)), 0) as retail_stock_value');
        }

        $inventory = $inventoryQuery->first();

        $productsQuery = Product::query()
            ->with(['countryPrices' => fn ($query) => $query
                ->when($isPartner, fn ($prices) => $prices->where('country_code', $partnerCountryCode)->where('is_available', true))
                ->orderBy('country_code')])
            ->leftJoinSub(clone $shopStock, 'shop_stock', 'shop_stock.product_id', '=', 'products.id')
            ->select('products.*')
            ->selectRaw('COALESCE(shop_stock.quantity, 0) as shop_quantity')
            ->when($isPartner, fn ($query) => $query->whereHas('countryPrices', fn ($prices) => $prices
                ->where('country_code', $partnerCountryCode)->where('is_available', true)))
            ->when(trim($this->search) !== '', function ($query) {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($search) use ($term) {
                    $search->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($this->stockFilter === 'out', fn ($query) => $query->whereRaw('COALESCE(shop_stock.quantity, 0) <= 0'))
            ->when($this->stockFilter === 'low', fn ($query) => $query->whereRaw('COALESCE(shop_stock.quantity, 0) > 0')
                ->whereNotNull('products.qts_seuil')->whereRaw('shop_stock.quantity <= products.qts_seuil'))
            ->when($this->stockFilter === 'available', fn ($query) => $query->whereRaw('COALESCE(shop_stock.quantity, 0) > 0')
                ->where(function ($stock) {
                    $stock->whereNull('products.qts_seuil')->orWhereRaw('shop_stock.quantity > products.qts_seuil');
                }));

        $Products = $productsQuery->orderBy('name')->paginate(12);

        return view('livewire.products.product-list', [
            'Products' => $Products,
            'inventory' => $inventory,
            'countryNames' => collect(WestAfricaShippingCatalog::countries())->map(fn ($country) => $country['label']),
            'isPartner' => $isPartner,
            'partnerCountryCode' => $partnerCountryCode,
        ])
            ->extends('layouts.admin')
            ->section('content');
    }
}
