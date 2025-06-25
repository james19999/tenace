<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\Auth;

class CardLivreur extends Component
{

    public $authuser;

    public function mount(){
       $this->authuser=Auth::user()->id;
    }
    public function render()
    {
        //  $startOfMonth = Carbon::now()->startOfMonth();
        // $endOfMonth = Carbon::now()->endOfMonth();

        // $orders = Order::where('user_id', $this->authuser)
        //                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
        //                ->get();

       $startOfWeek = Carbon::now()->startOfWeek();

        $orders = Order::where('user_id', $this->authuser)
                       ->where('created_at', '>=', $startOfWeek)
                       ->get();

        $totalLivraisons = $orders->count();
        $livrees = $orders->where('status', 'delivered')->count();
        $echouees = $orders->whereIn('status', ['ordered', 'canceled'])->count();
        $montantTotal = $orders->where('status', 'delivered')->sum('total');
        $fraisLivraison = $orders->sum('tax');
        return view('livewire.card-livreur'
    , [
            'totalLivraisons' => $totalLivraisons,
            'livrees' => $livrees,
            'echouees' => $echouees,
            'montantTotal' => $montantTotal,
            'fraisLivraison' => $fraisLivraison,
            'orders' => $orders
        ]
    );
    }
}
