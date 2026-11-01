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
use App\Services\StockModuleSettingsService;
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
        if ($user->user_type === User::ROLE_COMMERCIAL_MANAGER && $this->wholesaleLocationId()) {
            $locationIds->push($this->wholesaleLocationId());
        }

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
        $currentMonthStart = now()->startOfMonth();
        $previousMonthStart = $currentMonthStart->copy()->subMonthNoOverflow();
        $today = now()->startOfDay();
        $trackedCustody = $canSeeCentral ? 'general_cash' : 'shop';
        $cashFlowQuery = FinanceCashMovement::query()
            ->where(fn ($query) => $query->where('to_custody', $trackedCustody)->orWhere('from_custody', $trackedCustody))
            ->when(! $canSeeCentral, fn ($query) => $query->whereIn('stock_location_id', $locationIds));
        $openingBalance = (int) (clone $cashFlowQuery)->where('occurred_at', '<', $previousMonthStart)
            ->selectRaw('COALESCE(SUM(CASE WHEN to_custody = ? THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN from_custody = ? THEN amount ELSE 0 END), 0) as balance', [$trackedCustody, $trackedCustody])
            ->value('balance');
        $dailyCashChanges = (clone $cashFlowQuery)->whereBetween('occurred_at', [$previousMonthStart, $today->copy()->endOfDay()])
            ->selectRaw('DATE(occurred_at) as movement_date, SUM(CASE WHEN to_custody = ? THEN amount ELSE 0 END) - SUM(CASE WHEN from_custody = ? THEN amount ELSE 0 END) as net_change', [$trackedCustody, $trackedCustody])
            ->groupBy('movement_date')->orderBy('movement_date')->get()->keyBy('movement_date');
        $previousDailyBalances = [];
        $previousBalance = $openingBalance;
        $previousMonthEnd = $currentMonthStart->copy()->subDay();
        for ($day = $previousMonthStart->copy(); $day->lte($previousMonthEnd); $day->addDay()) {
            $key = $day->toDateString();
            $previousBalance += (int) ($dailyCashChanges->get($key)?->net_change ?? 0);
            $previousDailyBalances[(int) $day->format('j')] = $previousBalance;
        }
        $currentDailyBalances = [];
        $currentBalance = $previousBalance;
        for ($day = $currentMonthStart->copy(); $day->lte($today); $day->addDay()) {
            $key = $day->toDateString();
            $currentBalance += (int) ($dailyCashChanges->get($key)?->net_change ?? 0);
            $currentDailyBalances[(int) $day->format('j')] = $currentBalance;
        }
        $chartDays = (int) $currentMonthStart->daysInMonth;
        $lastPreviousDay = (int) $previousMonthEnd->format('j');
        $cashTrend = [
            'labels' => range(1, $chartDays),
            'current' => array_map(fn ($day) => $currentDailyBalances[$day] ?? null, range(1, $chartDays)),
            'previous' => array_map(fn ($day) => $previousDailyBalances[min($day, $lastPreviousDay)] ?? $openingBalance, range(1, $chartDays)),
            'current_month' => $currentMonthStart->translatedFormat('F Y'),
            'previous_month' => $previousMonthStart->translatedFormat('F Y'),
            'current_month_net' => $currentBalance - $previousBalance,
            'previous_month_net' => (int) array_sum($dailyCashChanges->filter(fn ($row, $date) => $date >= $previousMonthStart->toDateString() && $date <= $previousMonthEnd->toDateString())->pluck('net_change')->all()),
            'custody_label' => $canSeeCentral ? 'Caisse générale' : ($user->user_type === User::ROLE_COMMERCIAL_MANAGER ? 'Espèces du grossiste' : 'Espèces de ta boutique'),
        ];
        $monthlyCashTotals = function ($start, $end) use ($cashFlowQuery, $trackedCustody): array {
            $totals = (clone $cashFlowQuery)->whereBetween('occurred_at', [$start, $end])
                ->selectRaw('COALESCE(SUM(CASE WHEN to_custody = ? THEN amount ELSE 0 END), 0) as cash_in, COALESCE(SUM(CASE WHEN from_custody = ? THEN amount ELSE 0 END), 0) as cash_out', [$trackedCustody, $trackedCustody])
                ->first();

            return ['in' => (int) $totals->cash_in, 'out' => (int) $totals->cash_out];
        };
        $previousCashTotals = $monthlyCashTotals($previousMonthStart, $previousMonthEnd->copy()->endOfDay());
        $currentCashTotals = $monthlyCashTotals($currentMonthStart, $today->copy()->endOfDay());
        $cashFlowComparison = [
            'labels' => [$previousMonthStart->translatedFormat('M Y'), $currentMonthStart->translatedFormat('M Y').' · en cours'],
            'in' => [$previousCashTotals['in'], $currentCashTotals['in']],
            'out' => [$previousCashTotals['out'], $currentCashTotals['out']],
        ];
        $supplierPayables = 0;
        $overdueSupplierInvoices = 0;
        if ($user->isAdmin()) {
            $supplierInvoices = FinanceSupplierInvoice::query()->where('status', 'approved')
                ->whereIn('payment_status', ['unpaid', 'partial']);
            $supplierPayables = (clone $supplierInvoices)->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount'));
            $overdueSupplierInvoices = (clone $supplierInvoices)->whereNotNull('due_date')
                ->whereDate('due_date', '<', today())->count();
        }

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
            'cashTrend' => $cashTrend,
            'cashFlowComparison' => $cashFlowComparison,
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
        $locationIds = $this->remittanceLocationIds($user);
        $rows = FinanceCashRemittance::query()->with(['location', 'shopManager', 'collector', 'receiver'])
            ->when(! $canSeeAll, fn ($query) => $query->where(fn ($scope) => $scope->whereIn('stock_location_id', $locationIds)->orWhere('collector_id', $user->id)))
            ->latest('created_at')->paginate(15)->withQueryString();

        return view('finance.remittances.index', [
            'remittances' => $rows,
            'canSeeAll' => $canSeeAll,
            'canCreateRemittance' => $user->isManager() || $user->isWholesaleSalesRole(),
            'isWholesaleManager' => $user->isWholesaleSalesRole(),
        ]);
    }

    public function createRemittance(Request $request): View
    {
        $user = $request->user();
        $isWholesaleManager = $user->isWholesaleSalesRole();
        $allowedLocationIds = $isWholesaleManager
            ? collect(array_filter([$this->wholesaleLocationId()]))
            : StockLocation::query()->where('type', 'shop')->where('responsible_user_id', $user->id)->pluck('id');
        $locations = StockLocation::query()->where('type', 'shop')->where('is_active', true)
            ->whereIn('id', $allowedLocationIds)
            ->orderBy('name')->get(['id', 'name', 'responsible_user_id']);
        abort_if($locations->isEmpty(), 403, $isWholesaleManager ? 'La boutique grossiste n’est pas configurée.' : 'Aucune boutique ne t’est affectée.');

        $collectors = User::query()->where('active', true)->where('id', '<>', $user->id)
            ->whereNotIn('user_type', [User::ROLE_PARTNER])->orderBy('name')->get(['id', 'name', 'user_type']);

        return view('finance.remittances.create', compact('locations', 'collectors', 'isWholesaleManager'));
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
        $locationQuery = StockLocation::query()->whereKey($data['stock_location_id'])->where('type', 'shop')->where('is_active', true);
        if ($user->isWholesaleSalesRole()) {
            $locationQuery->whereKey($this->wholesaleLocationId() ?? 0);
        } elseif (! $user->isAdmin()) {
            $locationQuery->where('responsible_user_id', $user->id);
        }
        $location = $locationQuery->firstOrFail();

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

        return redirect()->route('finance.remittances.show', $remittance)->with('success', 'Bon créé et signé. Il attend la confirmation de la personne chargée de la collecte.');
    }

    public function showRemittance(Request $request, FinanceCashRemittance $remittance): View
    {
        $this->authorizeRemittance($request->user(), $remittance);
        $remittance->load(['location', 'shopManager', 'collector', 'receiver', 'movements.creator']);
        $cashOperations = $this->remittanceOperations($remittance);
        $isWholesaleRemittance = (int) $remittance->stock_location_id === (int) ($this->wholesaleLocationId() ?? 0);

        return view('finance.remittances.show', compact('remittance', 'cashOperations', 'isWholesaleRemittance'));
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
        $isWholesaleRemittance = (int) $remittance->stock_location_id === (int) ($this->wholesaleLocationId() ?? 0);

        return view('finance.remittances.print', compact('remittance', 'cashOperations', 'isWholesaleRemittance'));
    }

    public function expenses(Request $request): View
    {
        $user = $request->user();
        $canSeeAll = $user->isAdmin() || $user->isAccountant();
        $locationIds = StockLocation::query()->where('type', 'shop')->where('responsible_user_id', $user->id)->pluck('id');
        if ($user->isWholesaleSalesRole() && $this->wholesaleLocationId()) {
            $locationIds->push($this->wholesaleLocationId());
        }
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
        $isWholesaleManager = $user->isWholesaleSalesRole();
        $locations = StockLocation::query()->where('type', 'shop')->where('is_active', true)
            ->when($isWholesaleManager, fn ($query) => $query->whereKey($this->wholesaleLocationId() ?? 0))
            ->when(! $isWholesaleManager && ! $user->isAdmin(), fn ($query) => $query->where('responsible_user_id', $user->id))
            ->orderBy('name')->get(['id', 'name']);
        abort_if(($user->isManager() || $isWholesaleManager) && $locations->isEmpty(), 403, 'Aucun point de vente ne t’est affecté pour demander une dépense.');

        return view('finance.expenses.create', [
            'categories' => TypeExpensive::query()->orderBy('name')->get(),
            'locations' => $locations,
            'isShopManager' => $user->isManager() || $isWholesaleManager,
            'isWholesaleManager' => $isWholesaleManager,
        ]);
    }

    public function storeExpense(Request $request, FinanceExpenseWorkflowService $service): RedirectResponse
    {
        $data = $request->validate([
            'stock_location_id' => [$request->user()->isManager() ? 'required' : 'nullable', 'integer', 'exists:stock_locations,id'],
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
        if ($request->user()->isWholesaleSalesRole()) {
            $wholesaleLocationId = $this->wholesaleLocationId();
            abort_unless($wholesaleLocationId && StockLocation::query()->whereKey($wholesaleLocationId)->where('type', 'shop')->where('is_active', true)->exists(), 403, 'La boutique grossiste n’est pas configurée.');
            $data['stock_location_id'] = $wholesaleLocationId;
        }
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
        $isWholesaleExpense = $user->isWholesaleSalesRole()
            && (int) $payment->expense->stock_location_id === (int) ($this->wholesaleLocationId() ?? 0);
        abort_unless((int) $payment->paid_by === (int) $user->id
            || $user->isAdmin()
            || $user->isAccountant()
            || (int) $payment->expense->requested_by === (int) $user->id
            || $isExpenseLocationManager
            || $isWholesaleExpense, 403);

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
        if ($user->isWholesaleSalesRole()
            && (int) $remittance->stock_location_id === (int) ($this->wholesaleLocationId() ?? 0)) {
            return;
        }
        abort_unless((int) $remittance->collector_id === (int) $user->id
            || (int) $remittance->shop_manager_id === (int) $user->id, 403);
    }

    private function wholesaleLocationId(): ?int
    {
        $locationId = app(StockModuleSettingsService::class)->rules()['wholesale_sales_location_id'] ?? null;

        return $locationId ? (int) $locationId : null;
    }

    private function remittanceLocationIds(User $user)
    {
        if ($user->isAdmin() || $user->isAccountant()) {
            return StockLocation::query()->where('type', 'shop')->pluck('id');
        }
        if ($user->isWholesaleSalesRole()) {
            return collect(array_filter([$this->wholesaleLocationId()]));
        }

        return StockLocation::query()->where('type', 'shop')->where('responsible_user_id', $user->id)->pluck('id');
    }

    private function authorizeExpense(User $user, FinanceExpenseRequest $expense): void
    {
        if ($user->isAdmin() || $user->isAccountant() || (int) $expense->requested_by === (int) $user->id) {
            return;
        }
        if ($user->isWholesaleSalesRole()
            && (int) $expense->stock_location_id === (int) ($this->wholesaleLocationId() ?? 0)) {
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
