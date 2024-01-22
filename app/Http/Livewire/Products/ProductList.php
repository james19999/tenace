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
    public function AddToCartHighPrice($id,$name,$high_price) {
        Cart::instance('cart')->add($id,$name,1,$high_price)->associate(Product::class);

         return redirect()->back()->with('messages','Produit ajouter');

     }

     public function calcPrice(){
        $products = Product::all();
        $total = 0;
        foreach($products as $product){
            $total += $product->price_market * $product->qts_sell;
        }
        return $total;
    }
    public function calcPrices(){
        $products = Product::all();
        $total = 0;
        foreach($products as $product){
            $total += $product->price * $product->qts_sell;
        }
        return $total;
    }

    public function render()
    {    $totale=$this->calcPrice();
        $totales=$this->calcPrices();
        return view('livewire.products.product-list',
        ['Products'=>Product::all()->sortBy('name'),
         'totale'=>$totale,
         'totales'=>$totales

        ])
           ->extends('layouts.admin')
           ->section('content');
    }
}
