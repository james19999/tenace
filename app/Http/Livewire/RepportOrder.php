<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Orders\Order;

class RepportOrder extends Component
{
    public function render()
    {
        return view('livewire.repport-order',['orders'=>Order::all()]);
    }
}
