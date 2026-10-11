<?php

namespace App\Http\Livewire\Products;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Gloudemans\Shoppingcart\Facades\Cart;

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
        $inventoryQuery = Product::query()
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw('SUM(CASE WHEN qt_initial IS NULL OR qt_initial <= 0 THEN 1 ELSE 0 END) as out_of_stock_count')
            ->selectRaw('SUM(CASE WHEN qt_initial > 0 AND qts_seuil IS NOT NULL AND qt_initial <= qts_seuil THEN 1 ELSE 0 END) as low_stock_count')
            ->selectRaw('SUM(CASE WHEN qt_initial > 0 AND (qts_seuil IS NULL OR qt_initial > qts_seuil) THEN 1 ELSE 0 END) as available_count');

        if (Auth::user()?->isAdmin()) {
            $inventoryQuery
                ->selectRaw('COALESCE(SUM(price_market * qts_sell), 0) as purchase_stock_value')
                ->selectRaw('COALESCE(SUM(price * qts_sell), 0) as retail_stock_value');
        }

        $inventory = $inventoryQuery->first();

        $productsQuery = Product::query()
            ->when(trim($this->search) !== '', function ($query) {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($search) use ($term) {
                    $search->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($this->stockFilter === 'out', fn ($query) => $query->where(function ($stock) {
                $stock->whereNull('qt_initial')->orWhere('qt_initial', '<=', 0);
            }))
            ->when($this->stockFilter === 'low', fn ($query) => $query->where('qt_initial', '>', 0)
                ->whereNotNull('qts_seuil')->whereColumn('qt_initial', '<=', 'qts_seuil'))
            ->when($this->stockFilter === 'available', fn ($query) => $query->where('qt_initial', '>', 0)
                ->where(function ($stock) {
                    $stock->whereNull('qts_seuil')->orWhereColumn('qt_initial', '>', 'qts_seuil');
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
