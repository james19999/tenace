<?php

namespace App\Http\Livewire\Asides;

use Livewire\Component;
use App\Models\Orders\Order;


class Aside extends Component
{
    public $cartCount = 0;

    protected $listeners = ['cartUpdated' => 'refreshCartCount'];

    public function mount(): void
    {
        $this->refreshCartCount();
    }

    public function refreshCartCount(): void
    {
        $this->cartCount = (int) \Gloudemans\Shoppingcart\Facades\Cart::instance('cart')->count();
    }

    public function render()
    {
        return view('livewire.asides.aside', [
            'counts' => Order::where('brouillon', 0)->count('brouillon'),
        ]);
    }
}
