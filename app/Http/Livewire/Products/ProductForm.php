<?php

namespace App\Http\Livewire\Products;

use App\Models\Product;
use Livewire\Component;
use App\Models\EnterStock;

class ProductForm extends Component
{

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

            $product->save();
            if ($product!==null) {
                # code...
                $product->enterstocks()->create(
                    [
                        'qt_stock'=>$productData['qt_initial'],
                        'amount'=>$productData['price_by'],

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
