<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\Orders\Order;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;

class OrderList extends Component
{
   use WithPagination;

   public $showAssignModal = false;

public $assignOrderId = null;

public $selectedLivreur = null;
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

    public function updatedSelectedDate()
    {
        $this->resetPage();
    }

    public function openAssignModal($orderId)
{
    $order = Order::findOrFail($orderId);

    $this->assignOrderId = $order->id;

    // Si la commande possède déjà un livreur,
    // on le sélectionne automatiquement
    $this->selectedLivreur = $order->user_id;

    $this->showAssignModal = true;
}

public function closeAssignModal()
{
    $this->showAssignModal = false;

    $this->assignOrderId = null;

    $this->selectedLivreur = null;
}
public function assignOrder()
{
    $this->validate([
        'selectedLivreur' => 'required|exists:users,id',
    ], [
        'selectedLivreur.required' => 'Veuillez sélectionner un livreur.',
        'selectedLivreur.exists' => 'Le livreur sélectionné est invalide.',
    ]);

    // Vérifier que l'utilisateur est bien un livreur
    $livreur = User::where('id', $this->selectedLivreur)
        ->where('user_type', 'LVS')
        ->first();

    if (!$livreur) {



        return;
    }

    $order = Order::findOrFail($this->assignOrderId);

    $order->update([
        'user_id' => $livreur->id,
        'status_order'=>true,
        'take' => true,
    ]);

    $this->showAssignModal = false;

    $this->assignOrderId = null;

    $this->selectedLivreur = null;

    $this->resetPage();
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
            ->where('type', 'PR')
            ->where('status', 'ordered')
            ->selectRaw('DATE(date_order) as date')
            ->selectRaw('COUNT(*) as total_orders')
            ->groupByRaw('DATE(date_order)')
            ->orderBy('date')
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
                'user',
                'orderItems.product',
            ])
            ->whereDate('date_order', $this->selectedDate)
            ->where('type', 'PR')
            ->where('status', 'ordered')
            ->orderBy('date_order')
            ->paginate(10);


        /*
        |--------------------------------------------------------------------------
        | Nombre total de commandes pour la date sélectionnée
        |--------------------------------------------------------------------------
        */

        $selectedDateCount = Order::query()
            ->whereDate('date_order', $this->selectedDate)
            ->where('type', 'PR')
            ->where('status', 'ordered')
            ->count();


    $livreurs = User::query()
        ->where('user_type', 'LVS')
        ->orderBy('name')
        ->get();

        return view('livewire.order-list', [
            'dates' => $dates,
            'orders' => $orders,
            'selectedDate' => $this->selectedDate,
            'selectedDateCount' => $selectedDateCount,
            'livreurs' => $livreurs,
        ])        ->extends('layouts.admin')
        ->section('content');
    }
}
