<?php

namespace App\Http\Livewire\Products;

use App\Models\Product;
use Livewire\Component;
use Gloudemans\Shoppingcart\Facades\Cart;

class ProductList extends Component
{

    public function AddToCart($id,$name,$price) {
        Cart::instance('cart')->add($id,$name,1,$price)->associate(Product::class);

         return redirect()->back()->with('messages','Produit ajouter');

     }

    public function render()
    {
        return view('livewire.products.product-list',['Products'=>Product::latest()->get()])
           ->extends('layouts.admin')
           ->section('content');
    }
}
