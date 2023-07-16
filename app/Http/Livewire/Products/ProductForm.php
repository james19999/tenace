<?php

namespace App\Http\Livewire\Products;

use App\Models\Product;
use Livewire\Component;

class ProductForm extends Component
{

    public $products = [];

    public function addProduct()
    {
        $this->products[] = [
            'name' => '',
            'price' => '',

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

            $product->save();

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
