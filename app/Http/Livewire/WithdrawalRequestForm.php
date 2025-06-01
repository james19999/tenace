<?php

namespace App\Http\Livewire;

use App\Models\Wallet;
use Livewire\Component;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\Auth;

class WithdrawalRequestForm extends Component
{
        public $amount,  $details;

    public function submitWithdrawalRequest()
    {
        $this->validate([
            'amount' => 'required|numeric|min:5000',
            // 'method' => 'required|string|in:flooz,tmoney,bank',
            // 'details' => 'required|string|max:255',
        ]);

        $wallet = Wallet::firstOrCreate(['user_id' => Auth::id()]);

        if ($this->amount > $wallet->balance) {
            $this->addError('amount', 'Montant supérieur au solde disponible.');
            return;
        }

        WithdrawalRequest::create([
            'user_id' => Auth::id(),
            'amount' => $this->amount,
            // 'method' => $this->method,
            // 'details' => $this->details,
            'status' => 'en_attente',
        ]);

        // Optionnel : bloquer le montant dans le wallet
        $wallet->balance -= $this->amount;
        $wallet->save();

        $this->reset(['amount',]);
        session()->flash('message', 'Demande de virement envoyée avec succès.');
    }
    public function render()
    {
        $wallet = Wallet::where('user_id', Auth::id())->first();
          $myRequests = WithdrawalRequest::where('user_id', Auth::id())
                    ->latest()->get();
        return view('livewire.withdrawal-request-form', compact('wallet','myRequests'));
    }
}
