<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Orders\Order;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;

class OrderList extends Component
{
   use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $selectedDate;

    public function mount()
    {
        // Date sélectionnée par défaut
        $this->selectedDate = now()->toDateString();
    }

    public function changeType($orderId)
{
    $order = Order::findOrFail($orderId);

    // On change uniquement PR -> PU
    if ($order->type === 'PR') {
        $order->type = 'PU';
        $order->save();
    }
}

    /**
     * Sélection d'une date
     */
    public function selectDate($date)
    {
        $this->selectedDate = $date;

        // On revient à la première page
        $this->resetPage();
    }

    /**
     * Jour précédent
     */
    public function previousDay()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)
            ->subDay()
            ->toDateString();

        $this->resetPage();
    }

    /**
     * Jour suivant
     */
    public function nextDay()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)
            ->addDay()
            ->toDateString();

        $this->resetPage();
    }

    /**
     * Aujourd'hui
     */
    public function today()
    {
        $this->selectedDate = now()->toDateString();

        $this->resetPage();
    }

    public function render()
    {
        /*
        |--------------------------------------------------------------------------
        | Dates disponibles avec nombre de commandes
        |--------------------------------------------------------------------------
        */

        $dates = Order::query()
            ->whereNotNull('date_order')
            ->selectRaw('DATE(date_order) as date')
            ->selectRaw('COUNT(*) as total_orders')
            ->groupByRaw('DATE(date_order)')
            ->orderBy('date')
            ->where('status','ordered')
            ->where('type','PR')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Commandes de la date sélectionnée
        |--------------------------------------------------------------------------
        */

        $orders = Order::query()
            ->with([
                'costumer',
                'createduser',
            ])
            ->whereDate('date_order', $this->selectedDate)
            ->orderBy('date_order')
              ->where('status','ordered')
            ->where('type','PR')
            ->paginate(10);


        /*
        |--------------------------------------------------------------------------
        | Nombre total de commandes pour la date sélectionnée
        |--------------------------------------------------------------------------
        */

        $selectedDateCount = Order::query()
            ->whereDate('date_order', $this->selectedDate)
              ->where('status','ordered')
            ->where('type','PR')
            ->count();




        return view('livewire.order-list', [
            'dates' => $dates,
            'orders' => $orders,
            'selectedDateCount' => $selectedDateCount,
        ])        ->extends('layouts.admin')
        ->section('content');
    }
}
