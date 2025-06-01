<?php

namespace App\Http\Livewire;

use App\Models\Wallet;
use Livewire\Component;
use App\Models\WithdrawalRequest;

class WithdrawalRequestsTable extends Component
{
        public function accept($id)
    {
        $request = WithdrawalRequest::findOrFail($id);
        $request->status = 'accepté';
        $request->save();

        
    }

      public function reject($id)
    {
        $request = WithdrawalRequest::findOrFail($id);

        // 1. Remboursement du montant
        $wallet = Wallet::firstOrCreate(['user_id' => $request->user_id]);
        $wallet->balance += $request->amount;
        $wallet->save();

        // 2. Changement de statut
        $request->status = 'rejeté';
        $request->save();

        session()->flash('message', 'Demande rejetée et montant remboursé au partenaire.');
    }
    public function render()
    {
        return view('livewire.withdrawal-requests-table', [
            'requests' => WithdrawalRequest::latest()->with('user')->get()
        ]);
    }
}
