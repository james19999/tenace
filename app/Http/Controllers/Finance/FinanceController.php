<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceCashMovement;
use App\Models\FinanceCashClosure;
use App\Models\FinanceCashRemittance;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceExternalLoan;
use App\Models\FinanceEquipmentPurchaseRequest;
use App\Models\FinanceGeneralCashSetting;
use App\Models\FinanceTontineContributionRequest;
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
use Illuminate\Support\Facades\DB;
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
        $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
        $canSeeCentral = $user->isAdmin() || ($cashierId && (int) $cashierId === (int) $user->id);
        $canViewGeneralCashBalance = $user->isAdmin();
        $canAccessLoans = $user->isAdmin();
        $locationIds = $canSeeAll
            ? StockLocation::query()->where('type', 'shop')->pluck('id')
            : $this->shopLocationIdsFor($user);

        $remittances = FinanceCashRemittance::query()->when(! $canSeeAll, function ($query) use ($user, $locationIds): void {
            $query->where(function ($scope) use ($user, $locationIds): void {
                $scope->whereIn('stock_location_id', $locationIds)->orWhere('collector_id', $user->id);
            });
        });
        $expenses = FinanceExpenseRequest::query()->where(function ($query) use ($canSeeCentral): void {
            $query->whereNull('staff_advance_type')->orWhere('staff_advance_type', '<>', 'loan');
            if ($canSeeCentral) {
                $query->orWhere(fn ($loans) => $loans->where('staff_advance_type', 'loan')->whereIn('status', ['authorized_to_pay', 'partially_paid', 'paid']));
            }
        })->when(! $canSeeAll, function ($query) use ($user, $locationIds, $canSeeCentral): void {
            $query->where(function ($scope) use ($user, $locationIds, $canSeeCentral): void {
                $scope->where('requested_by', $user->id)->orWhereIn('stock_location_id', $locationIds);
                if ($canSeeCentral) {
                    $scope->orWhereIn('status', ['authorized_to_pay', 'partially_paid']);
                }
            });
        });
        $visibleRemittances = (clone $remittances)->whereIn('status', ['in_transit', 'discrepancy_open']);
        $movements = FinanceCashMovement::query()->with(['location', 'creator'])
            ->when(! $user->isAdmin(), function ($query) use ($canSeeAll, $locationIds, $user): void {
                $query->where('from_custody', '<>', 'general_cash')->where('to_custody', '<>', 'general_cash');
                if (! $canSeeAll) {
                    $query->where(function ($scope) use ($locationIds, $user): void {
                        $scope->whereIn('stock_location_id', $locationIds)
                            ->orWhereIn('remittance_id', FinanceCashRemittance::query()->select('id')->where('collector_id', $user->id));
                    });
                }
            });
        $currentMonthStart = now()->startOfMonth();
        $previousMonthStart = $currentMonthStart->copy()->subMonthNoOverflow();
        $today = now()->startOfDay();
        $trackedCustody = $canViewGeneralCashBalance ? 'general_cash' : 'shop';
        $cashFlowQuery = FinanceCashMovement::query()
            ->where(fn ($query) => $query->where('to_custody', $trackedCustody)->orWhere('from_custody', $trackedCustody))
            ->when(! $canViewGeneralCashBalance, fn ($query) => $query->whereIn('stock_location_id', $locationIds));
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
            'custody_label' => $canViewGeneralCashBalance ? 'Caisse générale' : ($canSeeAll ? 'Espèces des boutiques' : ($user->user_type === User::ROLE_COMMERCIAL_MANAGER ? 'Espèces du grossiste' : 'Espèces de ta boutique')),
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
            'generalCash' => $canViewGeneralCashBalance ? $this->ledger->balance('general_cash') : null,
            'shopCash' => ! $canViewGeneralCashBalance ? (int) $locationIds->sum(fn ($locationId) => $this->ledger->balance('shop', (int) $locationId)) : null,
            'inTransit' => (int) (clone $visibleRemittances)->sum('handed_over_amount')
                - (int) (clone $visibleRemittances)->sum('received_amount'),
            'pendingRemittances' => (clone $remittances)->whereIn('status', ['awaiting_collector', 'in_transit', 'discrepancy_open'])->count(),
            'pendingExpenses' => (clone $expenses)->whereIn('status', ['awaiting_finance', 'awaiting_director'])->count(),
            'supplierPayables' => $supplierPayables,
            'overdueSupplierInvoices' => $overdueSupplierInvoices,
            'canAccessLoans' => $canAccessLoans,
            'activeLoanBalance' => $canAccessLoans
                ? (int) FinanceExternalLoan::query()->where('status', 'active')->sum(DB::raw('total_repayable - amount_repaid'))
                : 0,
            'recentMovements' => $movements->latest('occurred_at')->paginate(10, ['*'], 'movements_page')->withQueryString(),
            'cashTrend' => $cashTrend,
            'cashFlowComparison' => $cashFlowComparison,
            'canSeeAll' => $canSeeAll,
            'canSeeCentral' => $canSeeCentral,
            'canViewGeneralCashBalance' => $canViewGeneralCashBalance,
            'hasOpeningBalance' => \Illuminate\Support\Facades\DB::table('finance_general_openings')->exists(),
            'cashClosures' => FinanceCashClosure::query()->with(['counter', 'reviewer'])
                ->when(! $user->isAdmin(), fn ($query) => $query->whereRaw('1 = 0'))
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

        return back()->with('success', 'Comptage validé ; l’écart a été enregistré dans la caisse générale.');
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
            'canCreateRemittance' => $user->isManager() || $user->hasWholesaleAccess(),
            'isWholesaleManager' => $user->hasWholesaleAccess(),
        ]);
    }

    public function createRemittance(Request $request): View
    {
        $user = $request->user();
        $isWholesaleManager = $user->hasWholesaleAccess();
        $allowedLocationIds = $isWholesaleManager
            ? collect(array_filter([$this->wholesaleLocationId($user)]))
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
        if ($user->hasWholesaleAccess()) {
            $locationQuery->whereKey($this->wholesaleLocationId($user) ?? 0);
        } elseif (! $user->isAdmin()) {
            $locationQuery->where('responsible_user_id', $user->id);
        }
        $location = $locationQuery->firstOrFail();

        $cashSummary = $this->ledger->shopCashSummary(
            (int) $location->id,
            $data['period_start'],
            $data['period_end'],
        );

        return response()->json([
            'expected_amount' => $this->ledger->expectedShopCash(
                (int) $location->id,
                $data['period_start'],
                $data['period_end'],
            ),
            'cash_summary' => $cashSummary,
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
        $isWholesaleRemittance = (int) $remittance->stock_location_id === (int) ($this->wholesaleLocationId($request->user()) ?? 0);

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
        $isWholesaleRemittance = (int) $remittance->stock_location_id === (int) ($this->wholesaleLocationId($request->user()) ?? 0);

        return view('finance.remittances.print', compact('remittance', 'cashOperations', 'isWholesaleRemittance'));
    }

    public function expenses(Request $request): View
    {
        $user = $request->user();
        $canSeeAll = $user->isAdmin() || $user->isAccountant();
        $cashSetting = FinanceGeneralCashSetting::query()->with('cashier')->find(1);
        $isAssignedCashier = $cashSetting
            && (int) $cashSetting->cashier_user_id === (int) $user->id;
        $locationIds = $this->shopLocationIdsFor($user);
        $rows = FinanceExpenseRequest::query()->where(function ($query) use ($isAssignedCashier): void {
                $query->whereNull('staff_advance_type')->orWhere('staff_advance_type', '<>', 'loan');
                if ($isAssignedCashier) {
                    $query->orWhere(fn ($loans) => $loans->where('staff_advance_type', 'loan')->whereIn('status', ['authorized_to_pay', 'partially_paid', 'paid']));
                }
            })->with(['location', 'requester', 'financeReviewer', 'director', 'receiptReceiver'])
            ->when(! $canSeeAll && ! $isAssignedCashier, fn ($query) => $query->where(fn ($scope) => $scope->where('requested_by', $user->id)->orWhereIn('stock_location_id', $locationIds)))
            ->when($isAssignedCashier && ! $canSeeAll, fn ($query) => $query->where(fn ($scope) => $scope
                ->where('requested_by', $user->id)
                ->orWhereIn('status', ['authorized_to_pay', 'partially_paid', 'paid'])
                ->orWhere('approval_route', 'director_direct')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($scope) => $scope->where('reference', 'like', '%'.$request->string('search').'%')->orWhere('beneficiary', 'like', '%'.$request->string('search').'%')->orWhere('description', 'like', '%'.$request->string('search').'%')->orWhereHas('requester', fn ($requester) => $requester->where('name', 'like', '%'.$request->string('search').'%')->orWhere('phone', 'like', '%'.$request->string('search').'%'))))
            ->latest('created_at')->paginate(15)->withQueryString();

        $cashiers = $user->isAdmin()
            ? User::query()->where('active', true)->whereIn('user_type', User::GENERAL_CASHIER_ELIGIBLE_ROLES)->orderBy('name')->get(['id', 'name', 'user_type'])
            : collect();
        $cashierPurchaseRequests = $isAssignedCashier
            ? FinanceEquipmentPurchaseRequest::query()->with(['assignee', 'creator', 'approver'])->where('status', 'authorized_to_pay')->latest()->paginate(10, ['*'], 'purchase_cash_page')->withQueryString()
            : null;
        $cashierPurchaseReceiptRequests = $isAssignedCashier
            ? FinanceEquipmentPurchaseRequest::query()->with(['assignee', 'issuer', 'receiptReceiver', 'returnedReceiver'])
                ->where('status', 'submitted_for_review')->whereNotNull('receipt_paths')
                ->where(function ($pendingReceipts): void {
                    $pendingReceipts->whereNull('receipt_received_at')
                        ->orWhere(fn ($pendingReturn) => $pendingReturn->where('returned_amount', '>', 0)->whereNull('returned_received_at'));
                })
                ->latest('report_submitted_at')->paginate(10, ['*'], 'purchase_receipt_page')->withQueryString()
            : null;
        $cashierPayableExpenses = $isAssignedCashier
            ? FinanceExpenseRequest::query()->with(['location', 'requester'])
                ->whereIn('status', ['authorized_to_pay', 'partially_paid'])
                ->where('requested_by', '<>', $user->id)
                ->latest('director_decided_at')->paginate(10, ['*'], 'cashier_expense_page')->withQueryString()
            : null;
        $cashierPayableExpenseCount = $isAssignedCashier
            ? FinanceExpenseRequest::query()->whereIn('status', ['authorized_to_pay', 'partially_paid'])->where('requested_by', '<>', $user->id)->count()
            : 0;
        $cashierPayableExpenseAmount = $isAssignedCashier
            ? (int) FinanceExpenseRequest::query()->whereIn('status', ['authorized_to_pay', 'partially_paid'])->where('requested_by', '<>', $user->id)->sum(DB::raw('amount - paid_amount'))
            : 0;
        $cashierTontineRequests = $isAssignedCashier
            ? FinanceTontineContributionRequest::query()->with(['tontine', 'requester'])
                ->where('status', 'awaiting_payment')->orderBy('requested_at')
                ->paginate(10, ['*'], 'cashier_tontine_page')->withQueryString()
            : null;
        $cashierPurchaseCount = $cashierPurchaseRequests?->total() ?? 0;
        $cashierPurchaseAmount = $isAssignedCashier
            ? (int) FinanceEquipmentPurchaseRequest::query()->where('status', 'authorized_to_pay')->sum('approved_amount')
            : 0;
        $directExpenseCategories = $isAssignedCashier ? TypeExpensive::query()->orderBy('name')->get(['id', 'name']) : collect();
        $directExpenseDirectors = $isAssignedCashier
            ? User::query()->where('active', true)->where('user_type', User::ROLE_ADMIN)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('finance.expenses.index', [
            'expenses' => $rows,
            'canSeeAll' => $canSeeAll,
            'cashSetting' => $cashSetting,
            'cashiers' => $cashiers,
            'isAssignedCashier' => $isAssignedCashier,
            'cashierPurchaseRequests' => $cashierPurchaseRequests,
            'cashierPurchaseReceiptRequests' => $cashierPurchaseReceiptRequests,
            'cashierPayableExpenses' => $cashierPayableExpenses,
            'cashierPayableExpenseCount' => $cashierPayableExpenseCount,
            'cashierPayableExpenseAmount' => $cashierPayableExpenseAmount,
            'cashierTontineRequests' => $cashierTontineRequests,
            'cashierPurchaseCount' => $cashierPurchaseCount,
            'cashierPurchaseAmount' => $cashierPurchaseAmount,
            'cashierGeneralBalance' => $isAssignedCashier && $request->user()->isAdmin() ? $this->ledger->balance('general_cash') : null,
            'directExpenseCategories' => $directExpenseCategories,
            'directExpenseDirectors' => $directExpenseDirectors,
            'directExpenseCompartments' => $isAssignedCashier
                ? \App\Models\FinanceCashCompartment::query()->whereIn('key', ['exploitation', 'dg_remuneration'])->orderBy('id')->get()
                : collect(),
        ]);
    }

    public function recordDirectDirectorExpense(Request $request, FinanceExpenseWorkflowService $service): RedirectResponse
    {
        $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
        abort_unless($cashierId && (int) $cashierId === (int) $request->user()->id, 403,
            'Seul le responsable désigné de la caisse générale peut enregistrer cette remise.');

        $data = $request->validate([
            'director_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('active', true)->where('user_type', User::ROLE_ADMIN))],
            'category_id' => ['required', 'integer', 'exists:type_expensives,id'],
            'compartment_id' => ['required', 'integer', Rule::exists('finance_cash_compartments', 'id')->where(fn ($query) => $query->whereIn('key', ['exploitation', 'dg_remuneration']))],
            'amount' => ['required', 'integer', 'min:1'],
            'beneficiary' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
        ]);
        abort_if((int) $data['director_user_id'] === (int) $request->user()->id, 422, 'Le caissier ne peut pas se présenter comme le DG demandeur.');

        $director = User::query()->where('active', true)->where('user_type', User::ROLE_ADMIN)->findOrFail($data['director_user_id']);
        $expense = $service->submit([
            'category_id' => $data['category_id'],
            'compartment_id' => $data['compartment_id'],
            'direct_director_expense' => true,
            'amount' => $data['amount'],
            'beneficiary' => $data['beneficiary'],
            'description' => $data['description'],
            'justification_note' => 'Dépense demandée directement par le DG auprès du caissier général.',
        ], $director, false, false, true);

        return redirect()->route('finance.expenses.index')->with('success', 'Demande de dépense directe transmise au DG. Aucun fonds ne sera décaissé avant sa validation.');
    }

    public function updateGeneralCashier(Request $request, \App\Services\FinanceAuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'cashier_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('active', true)->whereIn('user_type', User::GENERAL_CASHIER_ELIGIBLE_ROLES))],
        ]);

        $setting = DB::transaction(function () use ($request, $data, $audit): FinanceGeneralCashSetting {
            $setting = FinanceGeneralCashSetting::query()->lockForUpdate()->find(1);
            $before = $setting?->only(['cashier_user_id']);
            $setting = FinanceGeneralCashSetting::query()->updateOrCreate(
                ['id' => 1],
                ['cashier_user_id' => $data['cashier_user_id'], 'updated_by' => $request->user()->id],
            );
            $audit->record($setting, 'general_cashier_assigned', $before, $setting->only(['cashier_user_id']));

            return $setting;
        });

        $pendingCount = FinanceExpenseRequest::query()->whereIn('status', ['authorized_to_pay', 'partially_paid'])->count();
        if ($pendingCount > 0) {
            $setting->cashier?->notify(new \App\Notifications\FinanceWorkflowNotification(
                'Demandes de dépense à remettre',
                $pendingCount.' demande(s) autorisée(s) sont en attente de remise depuis la caisse générale.',
                route('finance.expenses.index'),
                'payments',
            ));
        }

        return back()->with('success', 'Responsable unique de la caisse générale défini : '.$setting->cashier?->name.'.');
    }

    public function expenseCategories(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $categories = TypeExpensive::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('finance.expenses.categories', compact('categories', 'search'));
    }

    public function storeExpenseCategory(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isManager(), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $name = trim($data['name']);
        $category = TypeExpensive::query()->firstOrCreate(['name' => $name]);

        return redirect()->route('finance.expenses.categories.index')->with('success', $category->wasRecentlyCreated
            ? 'Catégorie créée. Elle est maintenant disponible dans les demandes.'
            : 'Cette catégorie existe déjà.');
    }

    public function createExpense(Request $request): View
    {
        $user = $request->user();
        $isWholesaleManager = $user->hasWholesaleAccess();
        $locations = StockLocation::query()->where('type', 'shop')->where('is_active', true)
            ->when($isWholesaleManager, fn ($query) => $query->whereKey($this->wholesaleLocationId($user) ?? 0))
            ->when(! $isWholesaleManager && ! $user->isAdmin(), fn ($query) => $query->whereIn('id', $this->shopLocationIdsFor($user)))
            ->orderBy('name')->get(['id', 'name']);
        abort_if(($user->isManager() || $isWholesaleManager) && $locations->isEmpty(), 403, 'Aucun point de vente ne t’est affecté pour demander une dépense.');

        return view('finance.expenses.create', [
            'categories' => TypeExpensive::query()->orderBy('name')->get(['id', 'name']),
            'compartments' => $user->isAdmin() ? \App\Models\FinanceCashCompartment::query()->orderBy('id')->get() : collect(),
            'locations' => $locations,
            'isShopManager' => $user->isManager() || $isWholesaleManager,
            'isWholesaleManager' => $isWholesaleManager,
        ]);
    }

    public function storeExpense(Request $request, FinanceExpenseWorkflowService $service): RedirectResponse
    {
        $data = $request->validate([
            'stock_location_id' => [$request->user()->isManager() ? 'required' : 'nullable', 'integer', 'exists:stock_locations,id'],
            'category_id' => ['required', 'integer', 'exists:type_expensives,id'],
            'compartment_id' => [$request->user()->isAdmin() ? 'required' : 'nullable', 'integer', 'exists:finance_cash_compartments,id'],
            'description' => ['required', 'string', 'max:5000'],
            'amount' => ['required', 'integer', 'min:1'],
            'beneficiary' => ['required', 'string', 'max:160'],
            'budget_reference' => ['nullable', 'string', 'max:120'],
            'requested_payment_date' => ['nullable', 'date'],
            'justification' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'justification_exception' => ['nullable', 'string', 'max:2000', 'required_without:justification'],
        ]);
        if ($request->user()->hasWholesaleAccess()) {
            $wholesaleLocationId = $this->wholesaleLocationId($request->user());
            abort_unless($wholesaleLocationId && StockLocation::query()->whereKey($wholesaleLocationId)->where('type', 'shop')->where('is_active', true)->exists(), 403, 'La boutique grossiste n’est pas configurée.');
            $data['stock_location_id'] = $wholesaleLocationId;
        } elseif (! $request->user()->isAdmin() && ! empty($data['stock_location_id'])) {
            abort_unless($this->shopLocationIdsFor($request->user())->contains((int) $data['stock_location_id']), 403);
        }
        $data['justification_path'] = $request->file('justification')?->store('finance/expenses', 'local');
        $data['justification_note'] = $data['justification_exception'] ?? null;
        $expense = $service->submit($data, $request->user());

        $message = $request->user()->isAccountant()
            ? 'Demande transmise au DG pour décision. Si elle est autorisée, le caissier général enregistré pourra remettre les fonds.'
            : 'Demande transmise au responsable financier. Aucun paiement ne peut être fait avant son autorisation.';

        return redirect()->route('finance.expenses.show', $expense)->with('success', $message);
    }

    public function showExpense(Request $request, FinanceExpenseRequest $expense): View
    {
        $this->authorizeExpense($request->user(), $expense);
        $expense->load(['location', 'category', 'compartment', 'requester', 'financeReviewer', 'director', 'receiptReceiver', 'approvals.decider', 'payments.payer']);
        $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
        $isAssignedCashier = $cashierId && (int) $cashierId === (int) $request->user()->id;
        $directorApprovedCashierRequest = $expense->director_decided_at
            && (int) $expense->director_decided_by !== (int) $request->user()->id;
        $canPayExpense = $cashierId
            && $isAssignedCashier
            && ((int) $expense->requested_by !== (int) $request->user()->id || $directorApprovedCashierRequest);
        $canSubmitReceipt = (int) $expense->requested_by === (int) $request->user()->id;
        $canConfirmReceipt = ($request->user()->isAdmin() || $isAssignedCashier)
            && (int) $expense->requested_by !== (int) $request->user()->id
            && ! $expense->receipt_received_at;

        return view('finance.expenses.show', compact('expense', 'canPayExpense', 'canSubmitReceipt', 'canConfirmReceipt', 'isAssignedCashier'));
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
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);
        $service->pay($expense, $request->user(), (int) $data['amount'], $data['payment_reference'] ?? null);

        return back()->with('success', 'Remise effectuée depuis la caisse générale. La personne qui a créé la demande doit maintenant transmettre les reçus d’achat.');
    }

    public function submitExpenseReceipts(Request $request, FinanceExpenseRequest $expense, \App\Services\FinanceAuditService $audit): RedirectResponse
    {
        abort_unless((int) $expense->requested_by === (int) $request->user()->id, 403);
        abort_unless((int) $expense->paid_amount > 0, 409, 'Les reçus pourront être transmis après la remise des fonds.');
        $data = $request->validate([
            'receipts' => ['required', 'array', 'min:1', 'max:8'],
            'receipts.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $newPaths = collect($data['receipts'])->map(fn ($file) => $file->store('finance/expenses/receipts', 'local'))->all();
        $before = ['receipt_paths' => $expense->receipt_paths ?? []];
        $expense->receipt_paths = array_values(array_merge($expense->receipt_paths ?? [], $newPaths));
        $expense->receipts_submitted_at = now();
        $expense->save();
        $audit->record($expense, 'expense_receipts_submitted', $before, [
            'receipt_paths' => $expense->receipt_paths,
            'receipts_submitted_at' => $expense->receipts_submitted_at?->toDateTimeString(),
        ]);

        $recipients = User::query()->where('active', true)->where('user_type', User::ROLE_ADMIN)->get();
        $cashier = FinanceGeneralCashSetting::query()->with('cashier')->whereKey(1)->first()?->cashier;
        if ($cashier?->active) {
            $recipients->push($cashier);
        }
        \Illuminate\Support\Facades\Notification::send($recipients->unique('id'), new \App\Notifications\FinanceExpenseReportNotification($expense, 'Les reçus d’achat ont été transmis par '.$request->user()->name.'. Le caissier général doit confirmer leur réception.'));

        return back()->with('success', 'Reçus transmis à la Direction.');
    }

    public function confirmExpenseReceipt(Request $request, FinanceExpenseRequest $expense, \App\Services\FinanceAuditService $audit): RedirectResponse
    {
        $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
        $isAssignedCashier = $cashierId && (int) $cashierId === (int) $request->user()->id;
        abort_unless($request->user()->isAdmin() || $isAssignedCashier, 403);
        abort_unless((int) $expense->requested_by !== (int) $request->user()->id, 403, 'Tu ne peux pas confirmer la réception de ton propre reçu.');
        abort_unless((int) $expense->paid_amount > 0, 409, 'Le reçu ne peut être confirmé qu’après la remise des fonds.');
        abort_unless(! $expense->receipt_received_at, 409, 'La réception de ce reçu est déjà confirmée.');

        $before = $expense->only(['receipt_received_at', 'receipt_received_by']);
        $expense->receipt_received_at = now();
        $expense->receipt_received_by = $request->user()->id;
        $expense->save();
        $audit->record($expense, 'expense_receipt_received', $before, [
            'receipt_received_at' => $expense->receipt_received_at?->toDateTimeString(),
            'receipt_received_by' => $request->user()->id,
        ]);
        $expense->requester?->notify(new \App\Notifications\FinanceWorkflowNotification(
            'Réception du reçu confirmée',
            'La réception du reçu pour la demande '.$expense->reference.' a été confirmée par '.$request->user()->name.'.',
            route('finance.expenses.show', $expense->id),
            'task_alt',
        ));

        return back()->with('success', 'Réception du reçu confirmée.');
    }

    public function printExpensePayment(Request $request, int $payment): View
    {
        $payment = \App\Models\FinanceExpensePayment::query()
            ->with(['expense.location', 'payer', 'location'])
            ->findOrFail($payment);
        $user = $request->user();
        $isExpenseLocationManager = $payment->expense->stock_location_id
            && (int) StockLocation::query()->whereKey($payment->expense->stock_location_id)->value('responsible_user_id') === (int) $user->id;
        if ($isExpenseLocationManager && $this->isWholesaleLocation((int) $payment->expense->stock_location_id, $user) && ! $user->hasWholesaleAccess()) {
            $isExpenseLocationManager = false;
        }
        $isWholesaleExpense = $user->hasWholesaleAccess()
            && (int) $payment->expense->stock_location_id === (int) ($this->wholesaleLocationId($user) ?? 0);
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
        } elseif (in_array($kind, ['expense', 'payment', 'expense-receipt'], true)) {
            $record = $kind === 'expense'
                ? FinanceExpenseRequest::query()->findOrFail($id)
                : ($kind === 'payment' ? \App\Models\FinanceExpensePayment::query()->findOrFail($id) : FinanceExpenseRequest::query()->findOrFail($id));
            $expense = $kind === 'payment' ? $record->expense : $record;
            $this->authorizeExpense($request->user(), $expense);
            $path = match ($kind) {
                'expense' => $record->justification_path,
                'payment' => $record->proof_path,
                'expense-receipt' => $record->receipt_paths[(int) $request->query('file', -1)] ?? null,
            };
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
        if ($user->hasWholesaleAccess()
            && (int) $remittance->stock_location_id === (int) ($this->wholesaleLocationId($user) ?? 0)) {
            return;
        }
        $isWholesaleRemittance = $this->isWholesaleLocation((int) $remittance->stock_location_id, $user);
        abort_unless((int) $remittance->collector_id === (int) $user->id
            || (! $isWholesaleRemittance && (int) $remittance->shop_manager_id === (int) $user->id), 403);
    }

    private function wholesaleLocationId(?User $user = null): ?int
    {
        $locationId = app(StockModuleSettingsService::class)->rules()['wholesale_sales_location_id'] ?? null;

        return $user?->wholesaleResponsibilityLocationId() ?? ($locationId ? (int) $locationId : null);
    }

    private function remittanceLocationIds(User $user)
    {
        if ($user->isAdmin() || $user->isAccountant()) {
            return StockLocation::query()->where('type', 'shop')->pluck('id');
        }
        if ($user->hasWholesaleAccess()) {
            return collect(array_filter([$this->wholesaleLocationId($user)]));
        }

        return $this->shopLocationIdsFor($user);
    }

    /**
     * A user assigned to the wholesale shop receives that shop's accounting
     * scope alongside the permissions attached to their primary role.
     */
    private function shopLocationIdsFor(User $user)
    {
        $query = StockLocation::query()->where('type', 'shop');
        $wholesaleLocationId = $this->wholesaleLocationId($user);
        if ($user->hasWholesaleAccess()) {
            $query->where(fn ($scope) => $scope->where('responsible_user_id', $user->id)
                ->when($wholesaleLocationId, fn ($scope) => $scope->orWhereKey($wholesaleLocationId)));
        } elseif ($wholesaleLocationId) {
            $query->where('responsible_user_id', $user->id)->where('id', '<>', $wholesaleLocationId);
        } else {
            $query->where('responsible_user_id', $user->id);
        }

        return $query->pluck('id');
    }

    private function isWholesaleLocation(int $locationId, ?User $user = null): bool
    {
        return $locationId > 0 && $locationId === (int) ($this->wholesaleLocationId($user) ?? 0);
    }

    private function authorizeExpense(User $user, FinanceExpenseRequest $expense): void
    {
        if ($user->isAdmin() || $user->isAccountant() || (int) $expense->requested_by === (int) $user->id) {
            return;
        }
        $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
        if ($cashierId && (int) $cashierId === (int) $user->id && in_array($expense->status, ['authorized_to_pay', 'partially_paid', 'paid'], true)) {
            return;
        }
        if ($cashierId && (int) $cashierId === (int) $user->id && $expense->approval_route === 'director_direct') {
            return;
        }
        if ($user->hasWholesaleAccess()
            && (int) $expense->stock_location_id === (int) ($this->wholesaleLocationId($user) ?? 0)) {
            return;
        }
        abort_unless($expense->stock_location_id
            && (int) StockLocation::query()->whereKey($expense->stock_location_id)->value('responsible_user_id') === (int) $user->id
            && (! $this->isWholesaleLocation((int) $expense->stock_location_id, $user) || $user->hasWholesaleAccess()), 403);
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
