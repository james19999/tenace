<?php

namespace App\Http\Livewire\Asides;

use Livewire\Component;
use App\Models\Orders\Order;


class Aside extends Component
{
    public function render()
    {
        return view('livewire.asides.aside',['counts'=>Order::where('brouillon',0)->count('brouillon')]);
    }
}
