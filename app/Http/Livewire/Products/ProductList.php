<?php

namespace App\Http\Livewire\Products;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Gloudemans\Shoppingcart\Facades\Cart;
use App\Services\StockModuleSettingsService;

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
        Cart::instance('cart')->add($product->id, $product->name, 1, $product->price)->associate(Product::class);
        $this->cartUpdated();
    }

    public function AddToCartHighPrice($id): void
    {
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
            ->leftJoinSub(clone $shopStock, 'shop_stock', 'shop_stock.product_id', '=', 'products.id')
            ->select('products.*')
            ->selectRaw('COALESCE(shop_stock.quantity, 0) as shop_quantity')
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
        ])
            ->extends('layouts.admin')
            ->section('content');
    }
}
