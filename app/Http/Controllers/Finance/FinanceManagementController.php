<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\FinanceAsset;
use App\Models\FinanceBudget;
use App\Models\FinanceCashMovement;
use App\Models\FinanceCommissionSettlement;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceExpenseApproval;
use App\Models\FinanceEquipmentPurchaseRequest;
use App\Models\FinanceEquipmentPurchaseSetting;
use App\Models\FinanceStaffAdvance;
use App\Models\FinanceStaffAdvanceRepayment;
use App\Models\EmployeePayrollPeriod;
use App\Models\FinanceSupplierInvoice;
use App\Models\OrderPayment;
use App\Models\Orders\Order;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\FinanceAuditService;
use App\Services\FinanceCashLedgerService;
use App\Notifications\FinanceWorkflowNotification;
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
        $tab = in_array($request->query('tab'), ['receivables', 'planning', 'team', 'purchases'], true) ? $request->query('tab') : 'receivables';
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
                ->where(fn ($scope) => $scope->whereNull('requests.staff_advance_type')->orWhere('requests.staff_advance_type', '<>', 'loan'))
                ->when($budget->stock_location_id, fn ($query) => $query->where('requests.stock_location_id', $budget->stock_location_id), fn ($query) => $query->whereNull('requests.stock_location_id'))
                ->whereBetween('payments.paid_at', [$budget->period_start->startOfDay(), $budget->period_end->endOfDay()])
                ->sum('payments.amount');
        });
        $budgetTotal = $budgets->sum('amount');
        $budgetActualTotal = $budgets->sum('actual_amount');
        $budgets = $this->paginateCollection($budgets, $request, 'budget_page');

        $teamView = in_array($request->query('team_view'), ['requests', 'disburse', 'loans', 'create'], true) ? $request->query('team_view') : 'loans';
        $advanceSearch = trim((string) $request->query('advance_search', ''));
        $advances = FinanceStaffAdvance::query()->with(['employee', 'payrollPeriod', 'repayments.receiver'])
            ->when($advanceSearch !== '', fn ($query) => $query->whereHas('employee', fn ($employee) => $employee->where('name', 'like', '%'.$advanceSearch.'%')))
            ->latest('granted_on')->paginate(20, ['*'], 'advance_page')->withQueryString();
        $pendingEmployeeAdvances = FinanceExpenseRequest::query()->with(['staffMember', 'requester'])
            ->whereIn('staff_advance_type', ['salary_advance', 'loan'])->whereIn('status', ['awaiting_finance', 'awaiting_director'])->latest('submitted_at')->paginate(10, ['*'], 'staff_advance_page')->withQueryString();
        $loansToDisburse = FinanceExpenseRequest::query()->with('staffMember')
            ->where('staff_advance_type', 'loan')->whereIn('status', ['authorized_to_pay', 'partially_paid'])
            ->latest('director_decided_at')->paginate(10, ['*'], 'loan_disbursement_page')->withQueryString();
        $assets = FinanceAsset::query()->with('location')->where('status', 'in_service')->orderBy('name')->paginate(10, ['*'], 'asset_page')->withQueryString();
        $assets->getCollection()->transform(function (FinanceAsset $asset): FinanceAsset {
            $elapsedMonths = $this->completedMonths($asset->acquired_on, now());
            $depreciable = max(0, $asset->purchase_amount - $asset->residual_amount);
            $asset->estimated_book_value = max($asset->residual_amount, $asset->purchase_amount - (int) floor($depreciable * min($elapsedMonths, $asset->useful_life_months) / max(1, $asset->useful_life_months)));
            return $asset;
        });
        $purchaseRequests = FinanceEquipmentPurchaseRequest::query()->with(['assignee', 'creator', 'approver', 'issuer', 'reviewer'])
            ->latest()->paginate(10, ['*'], 'purchase_page')->withQueryString();
        $purchaseSetting = FinanceEquipmentPurchaseSetting::query()->with('assignee')->find(1);
        return view('finance.management.index', [
            'tab' => $tab,
            'teamView' => $teamView,
            'advanceSearch' => $advanceSearch,
            'receivables' => $receivables,
            'receivableTotal' => $receivableTotal,
            'budgets' => $budgets,
            'budgetTotal' => $budgetTotal,
            'budgetActualTotal' => $budgetActualTotal,
            'locations' => StockLocation::query()->where('type', 'shop')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => DB::table('type_expensives')->orderBy('name')->pluck('name'),
            'employees' => User::query()->where('active', true)->whereNotIn('user_type', [User::ROLE_PARTNER, User::ROLE_ADMIN])->orderBy('name')->get(['id', 'name', 'user_type']),
            'salaryPeriods' => EmployeePayrollPeriod::query()->with('employee')->orderByDesc('period_start')->limit(100)->get(),
            'advances' => $advances,
            'pendingEmployeeAdvances' => $pendingEmployeeAdvances,
            'loansToDisburse' => $loansToDisburse,
            'purchaseRequests' => $purchaseRequests,
            'purchaseSetting' => $purchaseSetting,
            'advanceOutstanding' => (int) FinanceStaffAdvance::query()->where('advance_type', 'loan')->sum(DB::raw('amount - repaid_amount')),
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
            'advance_type' => ['required', Rule::in(['loan', 'salary_advance'])],
            'payroll_period_id' => ['nullable', 'required_if:advance_type,salary_advance', 'integer', 'exists:employee_payroll_periods,id'],
            'repayment_method' => ['nullable', 'required_if:advance_type,loan', Rule::in(['fixed_amount', 'salary_percent'])],
            'repayment_value' => ['nullable', 'required_if:advance_type,loan', 'numeric', 'min:0.01', 'max:1000000000'],
        ]);
        if (($data['advance_type'] ?? null) === 'loan') {
            $max = $data['repayment_method'] === 'salary_percent' ? 100 : 1000000000;
            if ((float) $data['repayment_value'] > $max || ($data['repayment_method'] === 'fixed_amount' && floor((float) $data['repayment_value']) !== (float) $data['repayment_value'])) {
                throw ValidationException::withMessages(['repayment_value' => $data['repayment_method'] === 'salary_percent' ? 'Le pourcentage doit être compris entre 0,01 et 100 %.' : 'La retenue mensuelle doit être un montant entier valide.']);
            }
        }
        $employee = User::query()->where('active', true)->where('user_type', '<>', User::ROLE_PARTNER)->findOrFail($data['user_id']);
        $payrollPeriod = null;
        if ($data['advance_type'] === 'salary_advance') {
            $payrollPeriod = EmployeePayrollPeriod::query()
                ->where('user_id', $employee->id)
                ->findOrFail($data['payroll_period_id']);
            $alreadyPaid = (int) $payrollPeriod->payments()->where('kind', 'salary')->sum('amount');
            $alreadyAdvanced = (int) FinanceStaffAdvance::query()
                ->where('payroll_period_id', $payrollPeriod->id)
                ->sum('amount');
            $pendingAdvances = (int) FinanceExpenseRequest::query()
                ->where('staff_payroll_period_id', $payrollPeriod->id)
                ->whereIn('status', ['awaiting_finance', 'awaiting_director', 'authorized_to_pay', 'partially_paid'])
                ->sum(DB::raw('amount - paid_amount'));
            $remaining = max(0, (int) $payrollPeriod->salary_amount - $alreadyPaid - $alreadyAdvanced - $pendingAdvances);
            if ((int) $data['amount'] > $remaining) {
                throw ValidationException::withMessages(['amount' => 'L’avance dépasse le reste du salaire de ce mois ('.number_format($remaining, 0, ',', ' ').' F).']);
            }
        }
        $workflow->submit([
            'category_name' => $data['advance_type'] === 'salary_advance' ? 'Avance sur salaire' : 'Prêt au personnel',
            'description' => ($data['advance_type'] === 'salary_advance' ? 'Avance sur salaire' : 'Prêt au personnel').': '.$data['reason'],
            'beneficiary' => $employee->name,
            'amount' => $data['amount'],
            'requested_payment_date' => $data['granted_on'],
            'justification_note' => 'Demande au personnel : '.$data['reason'],
            'staff_user_id' => $employee->id,
            'staff_advance_type' => $data['advance_type'],
            'staff_payroll_period_id' => $payrollPeriod?->id,
            'staff_repayment_method' => $data['repayment_method'] ?? null,
            'staff_repayment_value' => $data['repayment_value'] ?? null,
        ], $request->user(), $request->user()->isAdmin());
        return back()->with('success', $request->user()->isAdmin()
            ? ($data['advance_type'] === 'loan' ? 'Prêt autorisé. Enregistre sa remise réelle ; le solde à récupérer sera suivi séparément des dépenses.' : 'Avance autorisée par le DG. Enregistre ensuite son paiement pour mettre à jour la caisse et le suivi du salarié.')
            : 'Demande d’avance transmise au circuit de validation. Elle sera inscrite au suivi après son paiement autorisé.');
    }

    public function decideEmployeeAdvance(Request $request, FinanceExpenseRequest $expense, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment' => ['nullable', 'string', 'max:2000', Rule::requiredIf(fn () => $request->input('decision') === 'rejected')],
            'repayment_method' => ['nullable', Rule::requiredIf(fn () => $request->input('decision') === 'approved' && $expense->staff_advance_type === 'loan'), Rule::in(['fixed_amount', 'salary_percent'])],
            'repayment_value' => ['nullable', Rule::requiredIf(fn () => $request->input('decision') === 'approved' && $expense->staff_advance_type === 'loan'), 'numeric', 'min:0.01', 'max:1000000000'],
        ]);
        abort_unless(in_array($expense->staff_advance_type, ['salary_advance', 'loan'], true) && $expense->staff_user_id, 404);
        abort_unless(in_array($expense->status, ['awaiting_finance', 'awaiting_director'], true), 409);
        if ($data['decision'] === 'approved' && $expense->staff_advance_type === 'loan') {
            if (($data['repayment_method'] ?? null) === 'salary_percent' && (float) $data['repayment_value'] > 100) {
                throw ValidationException::withMessages(['repayment_value' => 'Le pourcentage doit être compris entre 0,01 et 100 %.']);
            }
            if (($data['repayment_method'] ?? null) === 'fixed_amount' && floor((float) $data['repayment_value']) !== (float) $data['repayment_value']) {
                throw ValidationException::withMessages(['repayment_value' => 'La retenue mensuelle doit être un montant entier valide.']);
            }
        }

        DB::transaction(function () use ($expense, $data, $request, $audit): void {
            $locked = FinanceExpenseRequest::query()->lockForUpdate()->findOrFail($expense->id);
            abort_unless(in_array($locked->staff_advance_type, ['salary_advance', 'loan'], true) && in_array($locked->status, ['awaiting_finance', 'awaiting_director'], true), 409);
            $before = $locked->only(['status', 'director_decided_by', 'director_decided_at', 'staff_repayment_method', 'staff_repayment_value']);
            if ($data['decision'] === 'approved' && $locked->staff_advance_type === 'loan') {
                $locked->staff_repayment_method = $data['repayment_method'];
                $locked->staff_repayment_value = $data['repayment_value'];
            }
            $locked->status = $data['decision'] === 'approved' ? 'authorized_to_pay' : 'rejected';
            $locked->director_decided_by = $request->user()->id;
            $locked->director_decided_at = now();
            $locked->director_comment = $data['comment'] ?? null;
            $locked->save();

            FinanceExpenseApproval::query()->create([
                'expense_request_id' => $locked->id, 'stage' => 'director', 'decision' => $data['decision'],
                'decided_by' => $request->user()->id, 'comment' => $data['comment'] ?? null, 'decided_at' => now(),
            ]);
            $audit->record($locked, $data['decision'] === 'approved' ? 'director_approved' : 'director_rejected', $before, $locked->only(['status', 'director_decided_by', 'director_decided_at', 'staff_repayment_method', 'staff_repayment_value']), $data['comment'] ?? null);

            $subjectName = $locked->staff_advance_type === 'loan' ? 'prêt' : 'avance';
            $locked->requester?->notify(new FinanceWorkflowNotification(
                $data['decision'] === 'approved' ? ucfirst($subjectName).' accepté par la Direction' : 'Demande d’avance refusée',
                $data['decision'] === 'approved'
                    ? 'Ta demande de '.$subjectName.' '.$locked->reference.' est acceptée. Tu peux venir récupérer les fonds ; la retenue prévue sera appliquée au suivi mensuel de ton salaire.'
                    : 'Ta demande '.$locked->reference.' a été refusée.'.(! empty($data['comment']) ? ' Motif : '.$data['comment'] : ''),
                route('employees.self-service'),
                'payments',
            ));
        });

        return back()->with('success', $data['decision'] === 'approved' ? 'Demande acceptée. Le salarié peut récupérer les fonds ; le dossier attend leur remise réelle.' : 'Demande refusée.');
    }

    public function repayAdvance(Request $request, FinanceStaffAdvance $advance, FinanceCashLedgerService $ledger, FinanceAuditService $audit): RedirectResponse
    {
        $this->authorizeFinance($request->user());
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1'], 'received_at' => ['required', 'date', 'before_or_equal:now']]);
        DB::transaction(function () use ($advance, $data, $request, $ledger, $audit): void {
            $locked = FinanceStaffAdvance::query()->lockForUpdate()->findOrFail($advance->id);
            abort_if($locked->advance_type !== 'loan', 422, 'Une avance sur salaire est déduite du mois concerné et ne se rembourse pas dans cette liste.');
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

    public function updateAdvanceRepaymentPlan(Request $request, FinanceStaffAdvance $advance, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'repayment_method' => ['required', Rule::in(['fixed_amount', 'salary_percent'])],
            'repayment_value' => ['required', 'numeric', 'min:0.01', 'max:1000000000'],
        ]);
        if (($data['repayment_method'] === 'salary_percent' && (float) $data['repayment_value'] > 100)
            || ($data['repayment_method'] === 'fixed_amount' && floor((float) $data['repayment_value']) !== (float) $data['repayment_value'])) {
            throw ValidationException::withMessages(['repayment_value' => $data['repayment_method'] === 'salary_percent' ? 'Le pourcentage doit être compris entre 0,01 et 100 %.' : 'La retenue mensuelle doit être un montant entier valide.']);
        }

        DB::transaction(function () use ($advance, $data, $audit): void {
            $locked = FinanceStaffAdvance::query()->lockForUpdate()->findOrFail($advance->id);
            abort_unless($locked->advance_type === 'loan' && $locked->repaid_amount < $locked->amount, 404);
            $before = $locked->only(['repayment_method', 'repayment_value']);
            $locked->update($data);
            $audit->record($locked, 'staff_loan_repayment_plan_updated', $before, $locked->only(['repayment_method', 'repayment_value']));
        });

        return back()->with('success', 'Le plan de retenue mensuelle du prêt a été enregistré.');
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
                ->where(fn ($query) => $query->whereNull('staff_advance_type')->orWhere('staff_advance_type', '<>', 'loan'))
                ->whereBetween('requested_payment_date', [$start->toDateString(), $end->toDateString()])->selectRaw('COALESCE(SUM(amount - paid_amount),0) as due')->value('due');
            $loanDue = (int) FinanceExpenseRequest::query()->where('staff_advance_type', 'loan')
                ->whereIn('status', ['authorized_to_pay', 'partially_paid'])
                ->whereBetween('requested_payment_date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw('COALESCE(SUM(amount - paid_amount),0) as due')->value('due');
            $outflow = $supplierDue + $expenseDue + $loanDue;
            $balance += $averageInflow - $outflow;
            $weeks[] = ['start' => $start, 'end' => $end, 'inflow' => $averageInflow, 'supplier_due' => $supplierDue, 'expense_due' => $expenseDue, 'loan_due' => $loanDue, 'outflow' => $outflow, 'projected_balance' => $balance];
        }
        return ['opening_balance' => $ledger->balance('general_cash'), 'average_inflow' => $averageInflow, 'weeks' => $weeks];
    }
}
