<?php

namespace App\Http\Livewire\Products;

use Livewire\Component;
use App\Models\Costumer;
use Gloudemans\Shoppingcart\Facades\Cart;

class ProductCart extends Component
{

    public function destroy ($rowId){
        Cart::instance('cart')->remove($rowId);
        return back()->with('messages','Produit supprimé');
    }

    public function increment($rowId){
        $produit=Cart::instance('cart')->get($rowId);
        $qty=$produit->qty+1;
        Cart::instance('cart')->update($rowId,$qty);
         }


    public function decrement($rowId){

        $produit=Cart::instance('cart')->get($rowId);
        $qty=$produit->qty-1;
        Cart::instance('cart')->update($rowId,$qty);
      }

       public function total() {
        session()->put('subtotal',Cart::instance('cart')->subtotal());
       }

    public function render()
    {
        return view('livewire.products.product-cart',['Costumers'=>Costumer::all()->sortBy('name')])
        ->extends('layouts.admin')
        ->section('content');
        $this->total();
    }
}
