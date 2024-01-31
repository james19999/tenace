<?php

namespace App\Http\Livewire\Products;

use App\Models\Product;
use Livewire\Component;
use App\Models\EnterStock;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class ProductForm extends Component
{
    use WithFileUploads;
    public $products = [];

    public function addProduct()
    {
        $this->products[] = [
            'product_id' => '',
            'name' => '',
            'price' => '',
            'price_by'=>'',
            'qts_seuil'=>'',
            'qt_initial'=>'',
            'price_market'=>'',
            'high_price'=>'',
            'description'=>'',
            'img' => null,

        ];
    }

    public function removeProduct($index)
    {
        unset($this->products[$index]);
        $this->products = array_values($this->products);
    }

    public function saveProducts()
    {

        foreach ($this->products as $productData) {
            $product = new Product;
            $product->name = $productData['name'];
            $product->price = $productData['price'];
            $product->price_by = $productData['price_by'];
            $product->qts_seuil = $productData['qts_seuil'];
            $product->qt_initial = $productData['qt_initial'];
            $product->price_market = $productData['price_market'];
            $product->high_price = $productData['high_price'];
            $product->description = $productData['description'];

            if ($productData['img']) {
                // Handle image upload
                $imageName = time() . '_' . $productData['img']->getClientOriginalName();
                $productData['img']->storeAs('public/images', $imageName);
                $product->img =$imageName;
            }
            $product->save();
            if ($product!==null) {
                # code...
                $product->enterstocks()->create(
                    [
                        'qt_stock'=>$productData['qt_initial'],
                        'amount'=>$productData['price_by'],
                        'user_id'=>Auth::user()->id,


                    ]
                    );
             }

            redirect()->with('messages','Produit ajouter')->route('product');
        }

        // Clear the products array after saving
        $this->products = [];
    }

    public function render()
    {
        return view('livewire.products.product-form')
        ->extends('layouts.admin')
        ->section('content')
         ;
    }
}
