<?php

namespace App\Http\Livewire\Products;

use App\Models\Product;
use Livewire\Component;
use App\Models\EnterStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class ProductForm extends Component
{
    use WithFileUploads;
    public $products = [];

    public function mount(): void
    {
        $this->products = [$this->emptyProduct()];
    }

    protected function emptyProduct(): array
    {
        return [
            'product_id' => '',
            'name' => '',
            'price' => '',
            'price_by' => '',
            'qts_seuil' => '',
            'qt_initial' => '',
            'price_market' => '',
            'high_price' => '',
            'description' => '',
            'img' => null,
        ];
    }

    public function addProduct()
    {
        $this->products[] = $this->emptyProduct();
    }

    public function removeProduct($index)
    {
        unset($this->products[$index]);
        $this->products = array_values($this->products);
    }

    public function saveProducts()
    {
        $this->validate([
            'products' => ['required', 'array', 'min:1'],
            'products.*.name' => ['required', 'string', 'max:255'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
            'products.*.price_by' => ['required', 'numeric', 'min:0'],
            'products.*.price_market' => ['required', 'numeric', 'min:0'],
            'products.*.qt_initial' => ['required', 'integer', 'min:0'],
            'products.*.qts_seuil' => ['required', 'integer', 'min:0'],
            'products.*.high_price' => ['required', 'numeric', 'min:0'],
            'products.*.description' => ['nullable', 'string', 'max:2000'],
            'products.*.img' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'products.*.name' => 'nom du produit',
            'products.*.price' => 'prix de vente',
            'products.*.price_by' => 'montant d’achat du lot',
            'products.*.price_market' => 'prix d’achat unitaire',
            'products.*.qt_initial' => 'quantité initiale',
            'products.*.qts_seuil' => 'seuil d’alerte',
            'products.*.high_price' => 'prix de gros',
            'products.*.description' => 'description',
        ]);

        DB::transaction(function (): void {
            foreach ($this->products as $productData) {
                $product = new Product;
                $product->name = $productData['name'];
                $product->price = $productData['price'];
                $product->price_by = $productData['price_by'];
                $product->qts_seuil = $productData['qts_seuil'];
                $product->qt_initial = $productData['qt_initial'];
                $product->price_market = $productData['price_market'];
                $product->high_price = $productData['high_price'];
                $product->description = $productData['description'] ?? null;
                $product->save();

                if (! empty($productData['img'])) {
                    File::ensureDirectoryExists(public_path('image'));
                    $image = $productData['img'];
                    $imageName = Str::uuid().'.'.$image->getClientOriginalExtension();
                    $image->move(public_path('image'), $imageName);
                    $product->img = $imageName;
                    $product->save();
                }

                $product->enterstocks()->create([
                    'qt_stock' => $productData['qt_initial'],
                    'amount' => $productData['price_by'],
                    'user_id' => Auth::id(),
                ]);
            }
        });

        return redirect()->route('product')->with('messages', 'Produit(s) ajouté(s) avec succès.');
    }

    public function render()
    {
        return view('livewire.products.product-form')
        ->extends('layouts.admin')
        ->section('content')
         ;
    }
}
