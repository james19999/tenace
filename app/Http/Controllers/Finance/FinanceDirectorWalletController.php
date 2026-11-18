<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceDirectorWallet;
use App\Models\FinanceDirectorWalletCategory;
use App\Services\FinanceDirectorWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceDirectorWalletController extends Controller
{
    public function index(Request $request, FinanceDirectorWalletService $service): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $wallet = $service->openFor((int) $request->user()->id);
        $service->syncAvailableRemuneration($wallet);
        $summary = $service->summary($wallet);

        return view('finance.director-wallet.index', $summary);
    }

    public function storeOpening(Request $request, FinanceDirectorWalletService $service): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1', 'max:1000000000000']]);
        $wallet = $service->openFor((int) $request->user()->id);
        abort_if($wallet->entries()->where('entry_type', 'opening')->exists(), 409, 'Le montant de départ est déjà enregistré.');
        $service->recordCredit($wallet, (int) $data['amount'], 'opening', 'opening-balance:'.$wallet->id,
            'Solde de départ saisi par le DG', (int) $request->user()->id);

        return back()->with('success', 'Le solde de départ a été ajouté et réparti entre les catégories.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $wallet = FinanceDirectorWallet::query()->where('user_id', $request->user()->id)->firstOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('finance_director_wallet_categories', 'name')->where('wallet_id', $wallet->id)],
            'percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        abort_if($wallet->categories()->count() >= 10, 422, 'Le portefeuille accepte jusqu’à 10 catégories.');
        abort_if((int) $wallet->categories()->sum('percentage') + (int) $data['percentage'] > 100, 422, 'Le total des pourcentages ne peut pas dépasser 100 %.');
        $wallet->categories()->create($data);

        return back()->with('success', 'La catégorie a été créée. Ajuste les parts pour atteindre 100 %.');
    }

    public function updatePercentages(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $wallet = FinanceDirectorWallet::query()->where('user_id', $request->user()->id)->firstOrFail();
        $data = $request->validate([
            'percentages' => ['required', 'array'],
            'percentages.*' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        DB::transaction(function () use ($wallet, $data): void {
            $categories = $wallet->categories()->lockForUpdate()->get();
            if (count($data['percentages']) !== $categories->count()
                || $categories->contains(fn ($category) => ! array_key_exists((string) $category->id, $data['percentages']))) {
                abort(422, 'Actualise la page et renseigne chaque catégorie.');
            }
            $total = array_sum(array_map('intval', $data['percentages']));
            abort_if($total > 100, 422, 'Le total des parts ne peut pas dépasser 100 %.');
            foreach ($categories as $category) {
                $category->update(['percentage' => (int) $data['percentages'][$category->id]]);
            }
        });

        return back()->with('success', 'Les pourcentages sont enregistrés. Les soldes existants restent dans leur catégorie ; les nouveaux montants suivent la nouvelle répartition.');
    }

    public function destroyCategory(Request $request, FinanceDirectorWalletCategory $category): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless((int) $category->wallet?->user_id === (int) $request->user()->id, 404);
        abort_if($category->entries()->exists(), 409, 'Cette catégorie possède un historique et ne peut pas être supprimée.');
        $category->delete();

        return back()->with('success', 'La catégorie vide a été supprimée.');
    }

    public function withdraw(Request $request, FinanceDirectorWalletService $service): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'description' => ['required', 'string', 'max:255'],
        ]);
        $wallet = FinanceDirectorWallet::query()->where('user_id', $request->user()->id)->firstOrFail();
        $service->withdraw($wallet, (int) $data['category_id'], (int) $data['amount'], $data['description'], (int) $request->user()->id);

        return back()->with('success', 'Le retrait a été enregistré dans la catégorie choisie.');
    }
}
