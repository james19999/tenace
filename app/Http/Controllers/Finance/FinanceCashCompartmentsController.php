<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceCashCompartment;
use App\Models\FinanceCashCompartmentEntry;
use App\Models\FinanceCashMovement;
use App\Services\FinanceAuditService;
use App\Services\FinanceCashCompartmentsService;
use App\Services\FinanceCashLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceCashCompartmentsController extends Controller
{
    public function index(FinanceCashCompartmentsService $service, FinanceCashLedgerService $ledger): View
    {
        abort_unless(request()->user()->isAdmin() || request()->user()->isAccountant(), 403);
        $summary = $service->summary($ledger);
        $monthStart = now()->startOfMonth();
        $realReceipts = FinanceCashMovement::query()->where('to_custody', 'general_cash')
            ->where('from_custody', '<>', 'general_cash')->whereBetween('occurred_at', [$monthStart, now()])->sum('amount');
        $expenses = FinanceCashMovement::query()->where('from_custody', 'general_cash')
            ->where('to_custody', '<>', 'general_cash')->whereBetween('occurred_at', [$monthStart, now()])->sum('amount');
        $entries = FinanceCashCompartmentEntry::query()->with(['compartment', 'cashMovement'])
            ->latest('occurred_at')->paginate(20);

        return view('finance.compartments.index', compact('summary', 'realReceipts', 'expenses', 'entries'));
    }

    public function update(Request $request, FinanceCashCompartmentsService $service, FinanceCashLedgerService $ledger, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'percentages' => ['required', 'array', 'size:8'],
            'percentages.*' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        $before = FinanceCashCompartment::query()->orderBy('id')->get(['key', 'percentage'])->toArray();
        $distributed = $service->updatePercentages($data['percentages'], $request->user()->id);
        $after = FinanceCashCompartment::query()->orderBy('id')->get(['key', 'percentage'])->toArray();
        $subject = FinanceCashCompartment::query()->firstOrFail();
        $audit->record($subject, 'cash_compartment_percentages_updated', $before, $after,
            $distributed > 0 ? 'Les fonds non affectés ont été répartis automatiquement : '.$distributed.' F.' : null);

        return back()->with('success', $distributed > 0
            ? 'Répartition enregistrée. '.number_format($distributed, 0, ',', ' ').' F déjà présents dans la caisse ont été affectés.'
            : 'Pourcentages enregistrés. Les prochains encaissements réels seront répartis automatiquement.');
    }
}
