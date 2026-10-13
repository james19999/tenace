<?php

namespace App\Http\Livewire\Asides;

use Livewire\Component;
use App\Models\Orders\Order;
use App\Services\StockControlSidebarBadgeService;


class Aside extends Component
{
    public $cartCount = 0;
    public int $badgeRefresh = 0;

    protected $listeners = ['cartUpdated' => 'refreshCartCount'];

    public function mount(): void
    {
        $this->refreshCartCount();
    }

    public function refreshCartCount(): void
    {
        $this->cartCount = (int) \Gloudemans\Shoppingcart\Facades\Cart::instance('cart')->count();
    }

    public function refreshStockBadges(): void
    {
        $this->badgeRefresh++;
    }

    public function render()
    {
        return view('livewire.asides.aside', [
            'counts' => Order::where('brouillon', 0)->count('brouillon'),
            'stockBadges' => in_array(auth()->user()?->user_type, ['ADMINUSER', 'MNG'], true)
                ? app(StockControlSidebarBadgeService::class)->counts(auth()->user())
                : [],
        ]);
    }
}
