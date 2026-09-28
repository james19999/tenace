<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Orders\Order;

class DeliveryAlert extends Component
{
     public $lateOrdersCount = 0;
    public $todayOrdersCount = 0;

    public function mount()
    {
        $this->loadAlerts();
    }

    public function loadAlerts()
    {
        $today = Carbon::today();

        /*
        |--------------------------------------------------------------------------
        | Commandes en retard
        |--------------------------------------------------------------------------
        */

        $this->lateOrdersCount = Order::query()
            ->whereNotNull('date_order')
            ->whereDate('date_order', '<', $today)
            ->whereNotIn('status', [
                'delivered',
                'canceled',
            ])
            ->count();




        $this->todayOrdersCount = Order::query()
            ->whereNotNull('date_order')
            ->whereDate('date_order', $today)
            ->whereNotIn('status', [
                'delivered',
                'canceled',
            ])
            ->count();
    }

    public function render()
    {
        return view('livewire.delivery-alert');
    }
}
