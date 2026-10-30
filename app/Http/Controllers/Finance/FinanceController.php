<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceCashMovement;
use App\Models\FinanceCashClosure;
use App\Models\FinanceCashRemittance;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceSupplierInvoice;
use App\Models\StockLocation;
use App\Models\TypeExpensive;
use App\Models\User;
use App\Services\FinanceCashLedgerService;
use App\Services\FinanceCashClosureService;
use App\Services\FinanceCashRemittanceService;
use App\Services\FinanceExpenseWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(private readonly FinanceCashLedgerService $ledger)
    {
    }

    public function index(): View
    {
        $user = request()->user();
        $canSeeAll = $user->isAdmin() || $user->isAccountant();
        $canSeeCentral = $canSeeAll || $user->hasRole(User::ROLE_CASHIER);
        $locationIds = StockLocation::query()->where('type', 'shop')
            ->when(! $canSeeAll, fn ($query) => $query->where('responsible_user_id', $user->id))
            ->pluck('id');

        $remittances = FinanceCashRemittance::query()->when(! $canSeeAll, function ($query) use ($user, $locationIds): void {
            $query->where(function ($scope) use ($user, $locationIds): void {
                $scope->whereIn('stock_location_id', $locationIds)->orWhere('collector_id', $user->id);
            });
        });
        $expenses = FinanceExpenseRequest::query()->when(! $canSeeAll, function ($query) use ($user, $locationIds): void {
            $query->where(function ($scope) use ($user, $locationIds): void {
                $scope->where('requested_by', $user->id)->orWhereIn('stock_location_id', $locationIds);
            });
        });
        $visibleRemittances = (clone $remittances)->whereIn('status', ['in_transit', 'discrepancy_open']);
        $movements = FinanceCashMovement::query()->with(['location', 'creator'])
            ->when(! $canSeeAll, fn ($query) => $query->where(function ($scope) use ($locationIds, $user): void {
                $scope->whereIn('stock_location_id', $locationIds)
                    ->orWhereIn('remittance_id', FinanceCashRemittance::query()->select('id')->where('collector_id', $user->id));
            }));
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $weeklyMovements = (clone $movements)->whereBetween('occurred_at', [$weekStart, $weekEnd]);
        $supplierInvoices = FinanceSupplierInvoice::query()->where('status', 'approved')
            ->whereIn('payment_status', ['unpaid', 'partial']);
        $supplierPayables = (clone $supplierInvoices)->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount'));
        $overdueSupplierInvoices = (clone $supplierInvoices)->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())->count();

        return view('finance.dashboard', [
            'generalCash' => $canSeeCentral ? $this->ledger->balance('general_cash') : null,
            'shopCash' => ! $canSeeCentral ? (int) $locationIds->sum(fn ($locationId) => $this->ledger->balance('shop', (int) $locationId)) : null,
            'inTransit' => (int) (clone $visibleRemittances)->sum('handed_over_amount')
                - (int) (clone $visibleRemittances)->sum('received_amount'),
            'pendingRemittances' => (clone $remittances)->whereIn('status', ['awaiting_collector', 'in_transit', 'discrepancy_open'])->count(),
            'pendingExpenses' => (clone $expenses)->whereIn('status', ['awaiting_finance', 'awaiting_director'])->count(),
            'supplierPayables' => $supplierPayables,
            'overdueSupplierInvoices' => $overdueSupplierInvoices,
            'recentMovements' => $movements->latest('occurred_at')->paginate(10, ['*'], 'movements_page')->withQueryString(),
            'weeklySummary' => [
                'start' => $weekStart,
                'end' => $weekEnd,
                'sales_cash' => (clone $weeklyMovements)->where('movement_type', 'sale_cash_received')->sum('amount'),
                'expenses_paid' => (clone $weeklyMovements)->where('movement_type', 'expense_payment')->sum('amount'),
                'remitted' => (clone $weeklyMovements)->where('movement_type', 'remittance_handover')->sum('amount'),
                'received' => (clone $weeklyMovements)->whereIn('movement_type', ['central_cash_received', 'bank_deposit_confirmed'])->sum('amount'),
            ],
            'canSeeAll' => $canSeeAll,
            'canSeeCentral' => $canSeeCentral,
            'hasOpeningBalance' => \Illuminate\Support\Facades\DB::table('finance_general_openings')->exists(),
            'cashClosures' => FinanceCashClosure::query()->with(['counter', 'reviewer'])
                ->when(! $canSeeCentral, fn ($query) => $query->where('counted_by', $user->id))
                ->latest('closing_date')->limit(10)->get(),
            'shopOpeningsNeeded' => StockLocation::query()->where('type', 'shop')->where('is_active', true)
                ->when(! $user->isAdmin(), fn ($query) => $query->where('responsible_user_id', $user->id))
                ->whereNotIn('id', \Illuminate\Support\Facades\DB::table('finance_shop_openings')->select('stock_location_id'))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeCashClosure(Request $request, FinanceCashClosureService $service): RedirectResponse
    {
        $data = $request->validate([
            'closing_date' => ['required', 'date', 'before_or_equal:today'],
            'counted_amount' => ['required', 'integer', 'min:0'],
            'denominations' => ['nullable', 'array'],
            'denominations.*' => ['nullable', 'integer', 'min:0'],
            'count_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $service->count($data['closing_date'], (int) $data['counted_amount'], $request->user(), $data['denominations'] ?? null, $data['count_note'] ?? null);

        return back()->with('success', 'Comptage transmis pour contrôle par une autre personne autorisée.');
    }

    public function reviewCashClosure(Request $request, FinanceCashClosure $closure, FinanceCashClosureService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'reopened'])],
            'resolution_note' => ['nullable', 'string', 'max:2000', Rule::requiredIf(fn () => $request->input('decision') === 'reopened' || (int) $closure->variance_amount !== 0)],
        ]);
        $service->review($closure, $request->user(), $data['decision'], $data['resolution_note'] ?? null);

        return back()->with('success', $data['decision'] === 'approved' ? 'Clôture de caisse validée et historisée.' : 'Clôture rouverte. Un nouveau comptage est attendu.');
    }

    public function initializeCash(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:0'],
            'occurred_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $this->ledger->initializeGeneralCash((int) $data['amount'], $data['occurred_at'], $request->user(), $data['reason']);

        return back()->with('success', 'Fonds initial enregistré dans la caisse générale avec son motif et son auteur.');
    }

    public function initializeShopCash(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stock_location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'amount' => ['required', 'integer', 'min:0'],
            'occurred_at' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $this->ledger->initializeShopCash((int) $data['stock_location_id'], (int) $data['amount'], $data['occurred_at'], $request->user(), $data['reason']);

        return back()->with('success', 'Fonds initial de la boutique enregistré dans le journal financier.');
    }

    public function remittances(Request $request): View
    {
        $user = $request->user();
        $canSeeAll = $user->isAdmin() || $user->isAccountant();
        $locationIds = StockLocation::query()->where('type', 'shop')->where('responsible_user_id', $user->id)->pluck('id');
        $rows = FinanceCashRemittance::query()->with(['location', 'shopManager', 'collector', 'receiver'])
            ->when(! $canSeeAll, fn ($query) => $query->where(fn ($scope) => $scope->whereIn('stock_location_id', $locationIds)->orWhere('collector_id', $user->id)))
            ->latest('created_at')->paginate(15)->withQueryString();

        return view('finance.remittances.index', ['remittances' => $rows, 'canSeeAll' => $canSeeAll]);
    }

    public function createRemittance(Request $request): View
    {
        $user = $request->user();
        $locations = StockLocation::query()->where('type', 'shop')->where('is_active', true)
            ->when(! $user->isAdmin(), fn ($query) => $query->where('responsible_user_id', $user->id))
            ->orderBy('name')->get(['id', 'name', 'responsible_user_id']);
        abort_if($locations->isEmpty(), 403, 'Aucune boutique ne t’est affectée.');

        $collectors = User::query()->where('active', true)->where('id', '<>', $user->id)
            ->whereNotIn('user_type', [User::ROLE_PARTNER])->orderBy('name')->get(['id', 'name', 'user_type']);

        return view('finance.remittances.create', compact('locations', 'collectors'));
    }

    public function expectedRemittanceAmount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'stock_location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start', 'before_or_equal:today'],
        ]);
        $periodStart = \Illuminate\Support\Carbon::parse($data['period_start']);
        if ($periodStart->dayOfWeekIso !== 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'period_start' => 'La période du rapport doit commencer un lundi.',
            ]);
        }
        $user = $request->user();
        $location = StockLocation::query()->whereKey($data['stock_location_id'])
            ->where('type', 'shop')->where('is_active', true)
            ->when(! $user->isAdmin(), fn ($query) => $query->where('responsible_user_id', $user->id))
            ->firstOrFail();

        return response()->json([
            'expected_amount' => max(0, $this->ledger->expectedShopCash(
                (int) $location->id,
                $data['period_start'],
                $data['period_end'],
            )),
        ]);
    }

    public function storeRemittance(Request $request, FinanceCashRemittanceService $service): RedirectResponse
    {
        $data = $request->validate([
            'stock_location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start', 'before_or_equal:today'],
            'counted_amount' => ['required', 'integer', 'min:0'],
            'collector_id' => ['required', 'integer', 'exists:users,id'],
            'destination' => ['required', Rule::in(['general_cash', 'bank_deposit'])],
            'paper_voucher_reference' => ['nullable', 'string', 'max:120'],
            'discrepancy_note' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $periodStart = \Illuminate\Support\Carbon::parse($data['period_start']);
        if ($periodStart->dayOfWeekIso !== 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'period_start' => 'La période du rapport doit commencer un lundi.',
            ]);
        }
        $remittance = $service->prepare($data, $request->user());

        return redirect()->route('finance.remittances.show', $remittance)->with('success', 'Bon créé et signé par le responsable de boutique. Il attend la signature de la personne chargée de la collecte.');
    }

    public function showRemittance(Request $request, FinanceCashRemittance $remittance): View
    {
        $this->authorizeRemittance($request->user(), $remittance);
        $remittance->load(['location', 'shopManager', 'collector', 'receiver', 'movements.creator']);
        $cashOperations = $this->remittanceOperations($remittance);

        return view('finance.remittances.show', compact('remittance', 'cashOperations'));
    }

    public function signRemittance(Request $request, FinanceCashRemittance $remittance, FinanceCashRemittanceService $service): RedirectResponse
    {
        $data = $request->validate([
            'signed_voucher' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $signedVoucherPath = $request->file('signed_voucher')?->store('finance/remittances/signed-vouchers', 'local');
        $service->collectorSign($remittance, $request->user(), $signedVoucherPath);

        return back()->with('success', 'Collecte confirmée avec ton compte. Les fonds sont maintenant suivis en transit.');
    }

    public function confirmRemittance(Request $request, FinanceCashRemittance $remittance, FinanceCashRemittanceService $service): RedirectResponse
    {
        $data = $request->validate([
            'received_amount' => ['required', 'integer', 'min:0', 'max:'.$remittance->handed_over_amount],
            'bank_slip_reference' => ['nullable', 'string', 'max:120'],
            'bank_slip' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'discrepancy_note' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($remittance->destination === 'bank_deposit') {
            $request->validate([
                'bank_slip_reference' => ['required', 'string', 'max:120'],
                'bank_slip' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            ]);
        }
        $bankSlipPath = $request->file('bank_slip')?->store('finance/remittances', 'local');
        $service->confirmReceipt($remittance, $request->user(), (int) $data['received_amount'], $data['bank_slip_reference'] ?? null, $data['discrepancy_note'] ?? null, $bankSlipPath);

        return back()->with('success', 'Réception enregistrée dans la caisse générale.');
    }

    public function resolveRemittance(Request $request, FinanceCashRemittance $remittance, FinanceCashRemittanceService $service): RedirectResponse
    {
        $data = $request->validate(['resolution_note' => ['required', 'string', 'max:2000']]);
        $service->resolveDiscrepancy($remittance, $request->user(), $data['resolution_note']);

        return back()->with('success', 'Écart expliqué et validation du DG enregistrée.');
    }

    public function printRemittance(Request $request, FinanceCashRemittance $remittance): View
    {
        $this->authorizeRemittance($request->user(), $remittance);
        $remittance->load(['location', 'shopManager', 'collector', 'receiver']);
        $cashOperations = $this->remittanceOperations($remittance);

        return view('finance.remittances.print', compact('remittance', 'cashOperations'));
    }

    public function expenses(Request $request): View
    {
        $user = $request->user();
        $canSeeAll = $user->isAdmin() || $user->isAccountant();
        $locationIds = StockLocation::query()->where('type', 'shop')->where('responsible_user_id', $user->id)->pluck('id');
        $rows = FinanceExpenseRequest::query()->with(['location', 'requester', 'financeReviewer', 'director'])
            ->when(! $canSeeAll, fn ($query) => $query->where(fn ($scope) => $scope->where('requested_by', $user->id)->orWhereIn('stock_location_id', $locationIds)))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($scope) => $scope->where('reference', 'like', '%'.$request->string('search').'%')->orWhere('beneficiary', 'like', '%'.$request->string('search').'%')->orWhere('description', 'like', '%'.$request->string('search').'%')))
            ->latest('created_at')->paginate(15)->withQueryString();

        return view('finance.expenses.index', ['expenses' => $rows, 'canSeeAll' => $canSeeAll]);
    }

    public function createExpense(Request $request): View
    {
        $user = $request->user();
        return view('finance.expenses.create', [
            'categories' => TypeExpensive::query()->orderBy('name')->get(),
            'locations' => StockLocation::query()->where('type', 'shop')->where('is_active', true)
                ->when(! $user->isAdmin(), fn ($query) => $query->where('responsible_user_id', $user->id))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeExpense(Request $request, FinanceExpenseWorkflowService $service): RedirectResponse
    {
        $data = $request->validate([
            'stock_location_id' => ['nullable', 'integer', 'exists:stock_locations,id'],
            'category_id' => ['nullable', 'integer', 'exists:type_expensives,id', 'required_without:category_name'],
            'category_name' => ['nullable', 'string', 'max:120', 'required_without:category_id'],
            'description' => ['required', 'string', 'max:5000'],
            'amount' => ['required', 'integer', 'min:1'],
            'beneficiary' => ['required', 'string', 'max:160'],
            'budget_reference' => ['nullable', 'string', 'max:120'],
            'requested_payment_date' => ['nullable', 'date'],
            'justification' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'justification_exception' => ['nullable', 'string', 'max:2000', 'required_without:justification'],
        ]);
        $data['justification_path'] = $request->file('justification')?->store('finance/expenses', 'local');
        $data['justification_note'] = $data['justification_exception'] ?? null;
        $expense = $service->submit($data, $request->user());

        return redirect()->route('finance.expenses.show', $expense)->with('success', 'Demande transmise au responsable financier. Aucun paiement ne peut être fait avant son autorisation.');
    }

    public function showExpense(Request $request, FinanceExpenseRequest $expense): View
    {
        $this->authorizeExpense($request->user(), $expense);
        $expense->load(['location', 'category', 'requester', 'financeReviewer', 'director', 'approvals.decider', 'payments.payer']);

        return view('finance.expenses.show', compact('expense'));
    }

    public function financeDecision(Request $request, FinanceExpenseRequest $expense, FinanceExpenseWorkflowService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment' => ['nullable', 'string', 'max:2000', Rule::requiredIf(fn () => $request->input('decision') === 'rejected')],
        ]);
        $service->financeDecision($expense, $request->user(), $data['decision'], $data['comment'] ?? null);

        return back()->with('success', $data['decision'] === 'approved' ? 'Contrôle financier enregistré.' : 'Demande refusée et motif conservé.');
    }

    public function directorDecision(Request $request, FinanceExpenseRequest $expense, FinanceExpenseWorkflowService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment' => ['nullable', 'string', 'max:2000', Rule::requiredIf(fn () => $request->input('decision') === 'rejected')],
        ]);
        $service->directorDecision($expense, $request->user(), $data['decision'], $data['comment'] ?? null);

        return back()->with('success', $data['decision'] === 'approved' ? 'Décision du DG enregistrée.' : 'Demande refusée par le DG.');
    }

    public function payExpense(Request $request, FinanceExpenseRequest $expense, FinanceExpenseWorkflowService $service): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'custody_account' => ['required', Rule::in(['general_cash', 'shop'])],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $path = $request->file('proof')?->store('finance/expenses/payments', 'local');
        $locationId = $data['custody_account'] === 'shop' ? $expense->stock_location_id : null;
        $service->pay($expense, $request->user(), (int) $data['amount'], $data['custody_account'], $locationId, $data['payment_reference'] ?? null, $path);

        return back()->with('success', 'Décaissement enregistré. Le bon de paiement est disponible dans l’historique ci-dessous.');
    }

    public function printExpensePayment(Request $request, int $payment): View
    {
        $payment = \App\Models\FinanceExpensePayment::query()
            ->with(['expense.location', 'payer', 'location'])
            ->findOrFail($payment);
        $user = $request->user();
        $isExpenseLocationManager = $payment->expense->stock_location_id
            && (int) StockLocation::query()->whereKey($payment->expense->stock_location_id)->value('responsible_user_id') === (int) $user->id;
        abort_unless((int) $payment->paid_by === (int) $user->id
            || $user->isAdmin()
            || $user->isAccountant()
            || (int) $payment->expense->requested_by === (int) $user->id
            || $isExpenseLocationManager, 403);

        return view('finance.expenses.payment-voucher', compact('payment'));
    }

    public function downloadAttachment(Request $request, string $kind, int $id)
    {
        if (in_array($kind, ['remittance', 'signed-voucher'], true)) {
            $record = FinanceCashRemittance::query()->findOrFail($id);
            $this->authorizeRemittance($request->user(), $record);
            $path = $kind === 'signed-voucher' ? $record->signed_voucher_path : $record->bank_slip_path;
        } elseif (in_array($kind, ['expense', 'payment'], true)) {
            $record = $kind === 'expense'
                ? FinanceExpenseRequest::query()->findOrFail($id)
                : \App\Models\FinanceExpensePayment::query()->findOrFail($id);
            $expense = $kind === 'expense' ? $record : $record->expense;
            $this->authorizeExpense($request->user(), $expense);
            $path = $kind === 'expense' ? $record->justification_path : $record->proof_path;
        } else {
            abort(404);
        }
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }

    private function authorizeRemittance(User $user, FinanceCashRemittance $remittance): void
    {
        if ($user->isAdmin() || $user->isAccountant()) {
            return;
        }
        abort_unless((int) $remittance->collector_id === (int) $user->id
            || (int) $remittance->shop_manager_id === (int) $user->id, 403);
    }

    private function authorizeExpense(User $user, FinanceExpenseRequest $expense): void
    {
        if ($user->isAdmin() || $user->isAccountant() || (int) $expense->requested_by === (int) $user->id) {
            return;
        }
        abort_unless($expense->stock_location_id && (int) StockLocation::query()->whereKey($expense->stock_location_id)->value('responsible_user_id') === (int) $user->id, 403);
    }

    private function remittanceOperations(FinanceCashRemittance $remittance)
    {
        return FinanceCashMovement::query()->with('creator')
            ->where('stock_location_id', $remittance->stock_location_id)
            ->whereBetween('occurred_at', [$remittance->period_start->startOfDay(), $remittance->period_end->endOfDay()])
            ->whereIn('movement_type', ['sale_cash_received', 'expense_payment', 'cash_refund'])
            ->orderBy('occurred_at')->get();
    }
}
