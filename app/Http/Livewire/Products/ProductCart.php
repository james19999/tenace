<?php

namespace App\Http\Livewire\Products;

use Livewire\Component;
use App\Models\Costumer;
use App\Models\CostumerContactSetting;
use Gloudemans\Shoppingcart\Facades\Cart;

class ProductCart extends Component
{
    public $remis = 0;
    public $totals = 0;
    public $defaultFollowUpDays = 14;
    public $defaultFollowUpAt;

    public function mount()
    {
        $this->calculateTotal();
        $settings = CostumerContactSetting::first();
        $this->defaultFollowUpDays = $settings ? (int) $settings->default_follow_up_days : 14;
        $this->defaultFollowUpAt = old(
            'follow_up_at',
            now()->addDays($this->defaultFollowUpDays)->format('Y-m-d\\TH:i')
        );
    }

    public function updatedRemis()
    {
        $this->calculateTotal();
    }

    private function calculateTotal()
    {
        $this->totals = intval($this->remis) == 0
            ? Cart::instance('cart')->subtotal()
            : Cart::instance('cart')->subtotal() - (Cart::instance('cart')->subtotal() * intval($this->remis)) / 100;
    }


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
        return view('livewire.products.product-cart', [
            'Costumers' => Costumer::all()->sortBy('name'),
            'defaultFollowUpDays' => $this->defaultFollowUpDays,
            'defaultFollowUpAt' => $this->defaultFollowUpAt,
        ])
        ->extends('layouts.admin')
        ->section('content');
        $this->total();

    }
}
