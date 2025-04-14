<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Costumer;
use App\Models\Orders\Order;

class OrderFilter extends Component
{

    public $phone;
    public $month;
    public $orders = [];

    public function updated($field)
    {
        $this->filterOrders();
    }

    public function filterOrders()
    {
        if (!$this->phone || !$this->month) {
            $this->orders = [];
            return;
        }

        $customer = Costumer::where('phone', $this->phone)->first();

        if (!$customer) {
            $this->orders = [];
            return;
        }

        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $this->orders = Order::with('orderItems')
            ->where('costumer_id', $customer->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();
    }
    public function render()
    {
        return view('livewire.order-filter')
        ;
    }
}
