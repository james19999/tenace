<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\FinanceAsset;
use App\Models\FinanceBudget;
use App\Models\FinanceCashMovement;
use App\Models\FinanceCommissionSettlement;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceStaffAdvance;
use App\Models\FinanceStaffAdvanceRepayment;
use App\Models\FinanceSupplierInvoice;
use App\Models\OrderPayment;
use App\Models\Orders\Order;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\FinanceAuditService;
use App\Services\FinanceCashLedgerService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceManagementController extends Controller
{
    public function index(Request $request, FinanceCashLedgerService $ledger): View
    {
        $this->authorizeFinance($request->user());
        $tab = in_array($request->query('tab'), ['receivables', 'planning', 'team', 'assets'], true) ? $request->query('tab') : 'receivables';
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $receivables = $this->receivables($request);
        $receivableTotal = $receivables->sum('balance_due');
        $receivables = $this->paginateCollection($receivables, $request, 'receivable_page');
        $budgets = FinanceBudget::query()->with('location')->whereDate('period_start', '<=', $monthEnd)
            ->whereDate('period_end', '>=', $monthStart)->orderBy('category_name')->get();
        $budgets->each(function (FinanceBudget $budget): void {
            $budget->actual_amount = (int) DB::table('finance_expense_payments as payments')
                ->join('finance_expense_requests as requests', 'requests.id', '=', 'payments.expense_request_id')
                ->where('requests.category_name', $budget->category_name)
                ->when($budget->stock_location_id, fn ($query) => $query->where('requests.stock_location_id', $budget->stock_location_id), fn ($query) => $query->whereNull('requests.stock_location_id'))
                ->whereBetween('payments.paid_at', [$budget->period_start->startOfDay(), $budget->period_end->endOfDay()])
                ->sum('payments.amount');
        });
        $budgetTotal = $budgets->sum('amount');
        $budgetActualTotal = $budgets->sum('actual_amount');
        $budgets = $this->paginateCollection($budgets, $request, 'budget_page');

        $advances = FinanceStaffAdvance::query()->with(['employee', 'repayments.receiver'])->latest('granted_on')->paginate(10, ['*'], 'advance_page')->withQueryString();
        $commissionSummary = Commission::query()->whereBetween('created_at', [now()->startOfYear(), now()->endOfDay()])->select('user_id', DB::raw('SUM(amount) as accrued'))
            ->groupBy('user_id')->get()->map(function ($commission) {
                $user = User::query()->find((int) $commission->user_id);
                $paid = (int) FinanceCommissionSettlement::query()->where('user_id', (int) $commission->user_id)->whereDate('period_start', '>=', now()->startOfYear())->sum('amount');
                $pending = (int) FinanceExpenseRequest::query()->where('commission_user_id', (int) $commission->user_id)
                    ->whereIn('status', ['awaiting_finance', 'awaiting_director', 'authorized_to_pay', 'partially_paid'])
                    ->sum(DB::raw('amount - paid_amount'));
                return (object) ['user' => $user, 'accrued' => (int) $commission->accrued, 'paid' => $paid, 'due' => max(0, (int) $commission->accrued - $paid - $pending)];
            })->filter(fn ($row) => $row->user
                && $row->user->user_type !== User::ROLE_PARTNER
                && $row->due > 0)->sortByDesc('due')->values();
        $commissionSummary = $this->paginateCollection($commissionSummary, $request, 'commission_page');

        $assets = FinanceAsset::query()->with('location')->where('status', 'in_service')->orderBy('name')->paginate(10, ['*'], 'asset_page')->withQueryString();
        $assets->getCollection()->transform(function (FinanceAsset $asset): FinanceAsset {
            $elapsedMonths = $this->completedMonths($asset->acquired_on, now());
            $depreciable = max(0, $asset->purchase_amount - $asset->residual_amount);
            $asset->estimated_book_value = max($asset->residual_amount, $asset->purchase_amount - (int) floor($depreciable * min($elapsedMonths, $asset->useful_life_months) / max(1, $asset->useful_life_months)));
            return $asset;
        });
        return view('finance.management.index', [
            'tab' => $tab,
            'receivables' => $receivables,
            'receivableTotal' => $receivableTotal,
            'budgets' => $budgets,
            'budgetTotal' => $budgetTotal,
            'budgetActualTotal' => $budgetActualTotal,
            'locations' => StockLocation::query()->where('type', 'shop')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => DB::table('type_expensives')->orderBy('name')->pluck('name'),
            'employees' => User::query()->where('active', true)->where('user_type', '<>', User::ROLE_PARTNER)->orderBy('name')->get(['id', 'name', 'user_type']),
            'advances' => $advances,
            'advanceOutstanding' => (int) FinanceStaffAdvance::query()->sum(DB::raw('amount - repaid_amount')),
            'commissionSummary' => $commissionSummary,
            'assets' => $assets,
            'assetCostTotal' => (int) FinanceAsset::query()->where('status', 'in_service')->sum('purchase_amount'),
            'forecast' => $this->forecast($ledger),
            'receivableSearch' => $request->query('q'),
        ]);
    }

    public function storeBudget(Request $request, FinanceAuditService $audit): RedirectResponse
    {
        $this->authorizeFinance($request->user());
        $data = $request->validate([
            'category_name' => ['required', 'string', 'max:120'],
            'stock_location_id' => ['nullable', 'integer', Rule::exists('stock_locations', 'id')->where(fn ($query) => $query->where('type', 'shop')->where('is_active', true))],
            'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'amount' => ['required', 'integer', 'min:1'], 'note' => ['nullable', 'string', 'max:500'],
        ]);
        $data['created_by'] = $request->user()->id;
        $budget = FinanceBudget::query()->create($data);
        $audit->record($budget, 'budget_created', null, $budget->toArray());
        return back()->with('success', 'Budget enregistré. Les dépenses payées seront rapprochées automatiquement.');
    }

    public function storeAdvance(Request $request, \App\Services\FinanceExpenseWorkflowService $workflow): RedirectResponse
    {
        $this->authorizeFinance($request->user());
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'], 'amount' => ['required', 'integer', 'min:1'],
            'granted_on' => ['required', 'date', 'before_or_equal:today'], 'reason' => ['required', 'string', 'max:2000'],
        ]);
        $employee = User::query()->where('active', true)->where('user_type', '<>', User::ROLE_PARTNER)->findOrFail($data['user_id']);
        $workflow->submit([
            'category_name' => 'Avance au personnel',
            'description' => 'Avance au personnel : '.$data['reason'],
            'beneficiary' => $employee->name,
            'amount' => $data['amount'],
            'requested_payment_date' => $data['granted_on'],
            'justification_note' => 'Avance au personnel demandée pour : '.$data['reason'],
            'staff_user_id' => $employee->id,
        ], $request->user());
        return back()->with('success', 'Demande d’avance transmise au circuit de validation. Elle sera inscrite au suivi après son paiement autorisé.');
    }

    public function repayAdvance(Request $request, FinanceStaffAdvance $advance, FinanceCashLedgerService $ledger, FinanceAuditService $audit): RedirectResponse
    {
        $this->authorizeFinance($request->user());
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1'], 'received_at' => ['required', 'date', 'before_or_equal:now']]);
        DB::transaction(function () use ($advance, $data, $request, $ledger, $audit): void {
            $locked = FinanceStaffAdvance::query()->lockForUpdate()->findOrFail($advance->id);
            $remaining = $locked->amount - $locked->repaid_amount;
            if ($data['amount'] > $remaining) throw ValidationException::withMessages(['amount' => 'Le remboursement dépasse le solde de l’avance ('.number_format($remaining, 0, ',', ' ').' F).']);
            $repayment = FinanceStaffAdvanceRepayment::query()->create(['advance_id' => $locked->id, 'amount' => $data['amount'], 'received_at' => $data['received_at'], 'received_by' => $request->user()->id]);
            $locked->increment('repaid_amount', $data['amount']);
            $ledger->record([
                'movement_type' => 'staff_advance_repaid', 'from_custody' => 'external', 'to_custody' => 'general_cash',
                'amount' => $data['amount'], 'source_type' => 'finance_staff_advance_repayment', 'source_id' => $repayment->id,
                'source_key' => 'staff-advance-repayment:'.$repayment->id, 'source_reference' => $locked->reference,
                'occurred_at' => $data['received_at'], 'description' => 'Remboursement d’avance par '.$locked->employee()->value('name'),
                'created_by' => $request->user()->id,
            ]);
            $audit->record($locked, 'staff_advance_repayment', ['repaid_amount' => $remaining], ['repaid_amount' => $remaining + $data['amount']]);
        });
        return back()->with('success', 'Remboursement enregistré et ajouté à la caisse générale.');
    }

    public function settleCommission(Request $request, \App\Services\FinanceExpenseWorkflowService $workflow): RedirectResponse
    {
        $this->authorizeFinance($request->user());
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'], 'amount' => ['required', 'integer', 'min:1'],
            'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);
        $employee = User::query()->findOrFail($data['user_id']);
        $accrued = (int) Commission::query()->where('user_id', (string) $data['user_id'])->whereBetween('created_at', [$data['period_start'], Carbon::parse($data['period_end'])->endOfDay()])->sum('amount');
        $alreadyPaid = (int) FinanceCommissionSettlement::query()->where('user_id', $data['user_id'])
            ->whereDate('period_start', '<=', $data['period_end'])->whereDate('period_end', '>=', $data['period_start'])->sum('amount');
        $alreadyRequested = (int) FinanceExpenseRequest::query()->where('commission_user_id', $data['user_id'])
            ->whereDate('commission_period_start', '<=', $data['period_end'])->whereDate('commission_period_end', '>=', $data['period_start'])
            ->whereIn('status', ['awaiting_finance', 'awaiting_director', 'authorized_to_pay', 'partially_paid'])
            ->sum(DB::raw('amount - paid_amount'));
        if ($data['amount'] > max(0, $accrued - $alreadyRequested - $alreadyPaid)) throw ValidationException::withMessages(['amount' => 'La demande dépasse les commissions calculées restantes sur cette période.']);
        $workflow->submit([
            'category_name' => 'Commission du personnel',
            'description' => 'Règlement des commissions calculées du '.$data['period_start'].' au '.$data['period_end'].'.',
            'beneficiary' => $employee->name,
            'amount' => $data['amount'],
            'requested_payment_date' => today()->toDateString(),
            'justification_note' => 'Montant rapproché des commissions déjà calculées dans les ventes.',
            'commission_user_id' => $employee->id,
            'commission_period_start' => $data['period_start'],
            'commission_period_end' => $data['period_end'],
        ], $request->user());
        return back()->with('success', 'Demande de règlement transmise au circuit de validation. Le paiement sera enregistré dans le suivi après autorisation.');
    }

    public function storeAsset(Request $request, FinanceAuditService $audit): RedirectResponse
    {
        $this->authorizeFinance($request->user());
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'], 'category' => ['required', 'string', 'max:100'],
            'acquired_on' => ['required', 'date', 'before_or_equal:today'], 'purchase_amount' => ['required', 'integer', 'min:1'],
            'residual_amount' => ['required', 'integer', 'min:0', 'lte:purchase_amount'], 'useful_life_months' => ['required', 'integer', 'min:1', 'max:1200'],
            'stock_location_id' => ['nullable', 'integer', Rule::exists('stock_locations', 'id')->where(fn ($query) => $query->where('type', 'shop')->where('is_active', true))], 'supplier_name' => ['nullable', 'string', 'max:160'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], 'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['reference'] = 'TMP-'.Str::uuid();
        $data['created_by'] = $request->user()->id;
        if ($request->hasFile('document')) $data['document_path'] = $request->file('document')->store('finance/assets', 'local');
        unset($data['document']);
        $asset = FinanceAsset::query()->create($data);
        $asset->update(['reference' => sprintf('IMM-%s-%06d', now()->format('Y'), $asset->id)]);
        $audit->record($asset, 'asset_registered', null, $asset->toArray());
        return back()->with('success', 'Immobilisation enregistrée. Sa valeur nette est une estimation de suivi à faire valider par le cabinet.');
    }

    public function downloadAssetDocument(Request $request, FinanceAsset $asset)
    {
        $this->authorizeFinance($request->user());
        abort_unless($asset->document_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($asset->document_path), 404);
        return \Illuminate\Support\Facades\Storage::disk('local')->download($asset->document_path);
    }

    public function exportAssets(Request $request): StreamedResponse
    {
        $this->authorizeFinance($request->user());
        $assets = FinanceAsset::query()->with('location')->where('status', 'in_service')->orderBy('name')->get();
        return response()->streamDownload(function () use ($assets): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['TENACOS · Registre des immobilisations', 'Exporté le '.now()->format('d/m/Y H:i')], ';');
            fputcsv($output, ['Référence', 'Désignation', 'Catégorie', 'Date d’acquisition', 'Coût d’acquisition (F CFA)', 'Valeur résiduelle (F CFA)', 'Durée estimée (mois)', 'Valeur nette estimée (F CFA)', 'Lieu', 'Fournisseur'], ';');
            foreach ($assets as $asset) {
                $months = $this->completedMonths($asset->acquired_on, now());
                $depreciable = max(0, $asset->purchase_amount - $asset->residual_amount);
                $net = max($asset->residual_amount, $asset->purchase_amount - (int) floor($depreciable * min($months, $asset->useful_life_months) / max(1, $asset->useful_life_months)));
                fputcsv($output, [$asset->reference, $asset->name, $asset->category, $asset->acquired_on->format('d/m/Y'), $asset->purchase_amount, $asset->residual_amount, $asset->useful_life_months, $net, $asset->location?->name ?? 'Central', $asset->supplier_name], ';');
            }
            fclose($output);
        }, 'tenacos-immobilisations-'.today()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizeFinance(User $user): void
    {
        abort_unless($user->isAdmin() || $user->isAccountant(), 403);
    }

    private function completedMonths(Carbon $from, Carbon $to): int
    {
        $months = (($to->year - $from->year) * 12) + $to->month - $from->month;
        if ($to->day < $from->day) $months--;
        return max(0, $months);
    }

    private function receivables(Request $request): EloquentCollection
    {
        $search = trim((string) $request->query('q', ''));
        return Order::query()->with(['costumer', 'payments'])
            ->whereNotIn('status', ['delivered', 'canceled'])->whereNull('partner_id')->whereNull('public_order_link_id')
            ->when($search !== '', fn ($query) => $query->where(function ($where) use ($search): void {
                $where->where('code', 'like', '%'.$search.'%')
                    ->orWhereHas('costumer', fn ($customer) => $customer->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%'));
            }))
            ->latest('delivered_at')->latest('id')->get()->map(function (Order $order): Order {
                $order->receivable_total = max(0, (int) ($order->montant ?: ((int) $order->total + (int) $order->tax)));
                $payments = (int) $order->payments->whereIn('status', ['collected', 'confirmed'])->sum('amount');
                $order->receivable_paid = min($order->receivable_total, max((int) $order->paid_amount, $payments));
                $order->balance_due = max(0, $order->receivable_total - $order->receivable_paid);
                $order->balance_age_days = (int) $order->created_at->diffInDays(now());
                return $order;
            })->filter(fn (Order $order) => $order->balance_due > 0)->values();
    }

    private function paginateCollection($items, Request $request, string $pageName): LengthAwarePaginator
    {
        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => $pageName],
        );
    }

    private function forecast(FinanceCashLedgerService $ledger): array
    {
        $today = now()->startOfDay();
        $baselineStart = $today->copy()->startOfWeek()->subWeeks(4);
        $baselineEnd = $today->copy()->startOfWeek()->subSecond();
        $recentInflow = (int) FinanceCashMovement::query()->where('to_custody', 'general_cash')->whereBetween('occurred_at', [$baselineStart, $baselineEnd])->sum('amount');
        $averageInflow = (int) round($recentInflow / 4);
        $balance = $ledger->balance('general_cash');
        $weeks = [];
        for ($i = 0; $i < 13; $i++) {
            $start = $today->copy()->startOfWeek()->addWeeks($i);
            $end = $start->copy()->endOfWeek();
            $supplierDue = (int) FinanceSupplierInvoice::query()->where('status', 'approved')->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])->selectRaw('COALESCE(SUM(amount - paid_amount),0) as due')->value('due');
            $expenseDue = (int) FinanceExpenseRequest::query()->whereIn('status', ['authorized_to_pay', 'partially_paid'])
                ->whereBetween('requested_payment_date', [$start->toDateString(), $end->toDateString()])->selectRaw('COALESCE(SUM(amount - paid_amount),0) as due')->value('due');
            $outflow = $supplierDue + $expenseDue;
            $balance += $averageInflow - $outflow;
            $weeks[] = ['start' => $start, 'end' => $end, 'inflow' => $averageInflow, 'supplier_due' => $supplierDue, 'expense_due' => $expenseDue, 'outflow' => $outflow, 'projected_balance' => $balance];
        }
        return ['opening_balance' => $ledger->balance('general_cash'), 'average_inflow' => $averageInflow, 'weeks' => $weeks];
    }
}
