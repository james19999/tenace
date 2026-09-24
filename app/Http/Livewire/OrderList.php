<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Orders\Order;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;

class OrderList extends Component
{
   use WithPagination;
public $showDeleteModal = false;

public $deleteOrderId = null;
    protected $paginationTheme = 'bootstrap';

    public $selectedDate;

    public $showEditModal = false;

public $selectedOrderId;

public $editDate;

public $editTime;

public $showTypeModal = false;

public $typeOrderId;
    public function mount()
    {
        // Date sélectionnée par défaut
        $this->selectedDate = now()->toDateString();
    }

    public function openEditModal($orderId)
{
    $order = Order::findOrFail($orderId);

    $this->selectedOrderId = $order->id;

    $date = \Carbon\Carbon::parse($order->date_order);
    $datetime = \Carbon\Carbon::parse($order->time);

    $this->editDate = $date->format('Y-m-d');
    $this->editTime = $datetime->format('H:i');

    $this->showEditModal = true;
}

public function updateDeliveryDate()
{
    $this->validate([
        'editDate' => 'required|date',
        'editTime' => 'required|date_format:H:i',
    ]);

    $order = Order::findOrFail($this->selectedOrderId);

    $order->update([
        'date_order' => $this->editDate,
         'time'=> $this->editTime ,
    ]);

    $this->showEditModal = false;

    $this->reset([
        'selectedOrderId',
        'editDate',
        'editTime',
    ]);
}

public function closeEditModal()
{
    $this->showEditModal = false;

    $this->reset([
        'selectedOrderId',
        'editDate',
        'editTime',
    ]);
}
public function openChangeTypeModal($orderId)
{
    $order = Order::findOrFail($orderId);

    if ($order->type !== 'PR') {
        return;
    }

    $this->typeOrderId = $order->id;

    $this->showTypeModal = true;
}

public function confirmChangeType()
{
    $order = Order::findOrFail($this->typeOrderId);

    if ($order->type === 'PR') {

        $order->update([
            'type' => 'PU',
        ]);
    }

    $this->showTypeModal = false;

    $this->typeOrderId = null;
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

    public function openDeleteModal($orderId)
{
    $order = Order::findOrFail($orderId);

    $this->deleteOrderId = $order->id;

    $this->showDeleteModal = true;
}

public function closeDeleteModal()
{
    $this->showDeleteModal = false;

    $this->deleteOrderId = null;
}

public function deleteOrder()
{
    $order = Order::findOrFail($this->deleteOrderId);

    $order->delete();

    $this->showDeleteModal = false;

    $this->deleteOrderId = null;

    // Si tu utilises la pagination
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
