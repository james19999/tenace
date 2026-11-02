<?php

namespace App\Http\Controllers;

use App\Models\EmployeePayrollPayment;
use App\Models\EmployeeProfile;
use App\Models\EmployeePayrollPeriod;
use App\Models\FinanceAuditEvent;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceEquipmentPurchaseSetting;
use App\Models\FinanceStaffAdvance;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\FinanceCashLedgerService;
use App\Services\FinanceExpenseWorkflowService;
use App\Services\StockModuleSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeePayrollController extends Controller
{
    public function selfService(Request $request): View
    {
        return $this->renderSelfService($request, 'payroll');
    }

    public function selfServicePurchases(Request $request): View
    {
        return $this->renderSelfService($request, 'purchases');
    }

    private function renderSelfService(Request $request, string $activePage): View
    {
        $employee = $request->user();
        abort_unless($this->isEmployeeRole($employee), 403);
        $employeeShopLocation = $employee->primaryStockLocation;
        if ($employee->user_type === User::ROLE_WHOLESALER) {
            $wholesaleLocationId = app(StockModuleSettingsService::class)->rules()['wholesale_sales_location_id'] ?? null;
            if ($wholesaleLocationId) {
                $employeeShopLocation = StockLocation::query()->find($wholesaleLocationId) ?? $employeeShopLocation;
            }
        }
        $profile = EmployeeProfile::query()->firstOrCreate(['user_id' => $employee->id]);
        $employee->setRelation('employeeProfile', $profile);

        $periods = EmployeePayrollPeriod::query()->where('user_id', $employee->id)
            ->with(['payments.payer', 'staffAdvances.creator', 'staffAdvances.financeExpensePayment.expense'])
            ->orderByDesc('period_start')->limit(12)->get();
        $periods->each(fn (EmployeePayrollPeriod $period) => $period->setAttribute('loan_deduction_due', $this->monthlyLoanDeductionDue($employee, $period)));
        $advanceRequests = FinanceExpenseRequest::query()->with(['approvals.decider', 'payments.payer'])
            ->where('staff_user_id', $employee->id)->whereNotNull('staff_advance_type')
            ->latest('submitted_at')->limit(30)->get();
        $personalLoans = FinanceStaffAdvance::query()->with(['repayments.receiver'])
            ->where('user_id', $employee->id)->where('advance_type', 'loan')
            ->latest('granted_on')->limit(30)->get();
        $currentPeriod = EmployeePayrollPeriod::query()->where('user_id', $employee->id)->whereDate('period_start', now()->startOfMonth())->first();
        $currentPeriodSalary = (int) ($currentPeriod?->salary_amount ?? $profile->monthly_salary ?? 0);
        $currentPaid = (int) ($currentPeriod?->payments()->where('kind', 'salary')->sum('amount') ?? 0);
        $currentAdvanced = $currentPeriod ? (int) FinanceStaffAdvance::query()->where('payroll_period_id', $currentPeriod->id)->sum('amount') : 0;
        $currentPending = $currentPeriod ? (int) FinanceExpenseRequest::query()->where('staff_payroll_period_id', $currentPeriod->id)
            ->whereIn('status', ['awaiting_finance', 'awaiting_director', 'authorized_to_pay', 'partially_paid'])->sum(DB::raw('amount - paid_amount')) : 0;
        $availableForAdvance = max(0, $currentPeriodSalary - $currentPaid - $currentAdvanced - $currentPending);
        $purchaseRequests = class_exists(\App\Models\FinanceEquipmentPurchaseRequest::class)
            ? \App\Models\FinanceEquipmentPurchaseRequest::query()->where('assigned_to', $employee->id)->latest()->limit(20)->get()
            : collect();
        $purchaseSetting = FinanceEquipmentPurchaseSetting::query()->with('assignee')->find(1);
        $purchaseAssigneeId = $purchaseSetting?->assignee_user_id;
        $canCreatePurchases = (int) $purchaseAssigneeId === (int) $employee->id;
        $canManagePurchases = $canCreatePurchases || $purchaseRequests->isNotEmpty();
        abort_unless($activePage !== 'purchases' || $canManagePurchases, 404);

        return view('employees.self-service', compact('employee', 'employeeShopLocation', 'profile', 'periods', 'advanceRequests', 'personalLoans', 'purchaseRequests', 'purchaseSetting', 'availableForAdvance', 'activePage', 'canManagePurchases', 'canCreatePurchases'));
    }

    public function requestOwnAdvance(Request $request, FinanceExpenseWorkflowService $workflow): RedirectResponse
    {
        $employee = $request->user();
        abort_unless($this->isEmployeeRole($employee), 403);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:2000'],
            'advance_type' => ['required', Rule::in(['salary_advance', 'loan'])],
        ]);
        $period = null;
        if ($data['advance_type'] === 'salary_advance') {
            $profile = EmployeeProfile::query()->where('user_id', $employee->id)->firstOrFail();
            if (! $profile->monthly_salary || ! $profile->salary_effective_from) {
                throw ValidationException::withMessages(['amount' => 'Ton salaire n’est pas encore renseigné. Contacte l’administration pour demander une avance sur salaire.']);
            }

            $periodStart = now()->startOfMonth();
            if ($periodStart->lt($profile->salary_effective_from->copy()->startOfMonth())) {
                throw ValidationException::withMessages(['amount' => 'Le salaire convenu ne s’applique pas encore à ce mois.']);
            }
            $period = EmployeePayrollPeriod::query()->where('user_id', $employee->id)->whereDate('period_start', $periodStart)->first();
            if (! $period) {
                $period = EmployeePayrollPeriod::query()->create([
                    'reference' => 'TMP-'.Str::uuid(), 'user_id' => $employee->id,
                    'period_start' => $periodStart->toDateString(), 'salary_amount' => $profile->monthly_salary,
                    'note' => 'Suivi créé lors d’une demande d’avance par l’employé.', 'created_by' => $employee->id,
                ]);
                $period->update(['reference' => sprintf('SAL-%s-%06d', $periodStart->format('Y'), $period->id)]);
                FinanceAuditEvent::query()->create([
                    'subject_type' => 'EmployeePayrollPeriod', 'subject_id' => $period->id, 'action' => 'salary_period_created',
                    'after_values' => ['reference' => $period->reference, 'employee' => $employee->name, 'period' => $periodStart->format('Y-m'), 'salary_amount' => $period->salary_amount],
                    'reason' => 'Suivi ouvert lors d’une demande d’avance sur salaire.', 'user_id' => $employee->id, 'created_at' => now(),
                ]);
            }

            $alreadyPaid = (int) $period->payments()->where('kind', 'salary')->sum('amount');
            $alreadyAdvanced = (int) FinanceStaffAdvance::query()->where('payroll_period_id', $period->id)->sum('amount');
            $pendingAdvances = (int) FinanceExpenseRequest::query()->where('staff_payroll_period_id', $period->id)
                ->whereIn('status', ['awaiting_finance', 'awaiting_director', 'authorized_to_pay', 'partially_paid'])
                ->sum(DB::raw('amount - paid_amount'));
            $available = max(0, (int) $period->salary_amount - $alreadyPaid - $alreadyAdvanced - $pendingAdvances);
            if ((int) $data['amount'] > $available) {
                throw ValidationException::withMessages(['amount' => 'Le montant dépasse le solde disponible de '.number_format($available, 0, ',', ' ').' F CFA.']);
            }
        }

        $isLoan = $data['advance_type'] === 'loan';
        $workflow->submit([
            'category_name' => $isLoan ? 'Prêt au personnel' : 'Avance sur salaire',
            'description' => ($isLoan ? 'Demande de prêt' : 'Demande d’avance sur salaire').' de '.$employee->name.' : '.$data['reason'],
            'beneficiary' => $employee->name,
            'amount' => $data['amount'],
            'requested_payment_date' => today()->toDateString(),
            'justification_note' => ($isLoan ? 'Demande personnelle de prêt à rembourser séparément : ' : 'Demande personnelle d’avance sur salaire : ').$data['reason'],
            'staff_user_id' => $employee->id,
            'staff_advance_type' => $data['advance_type'],
            'staff_payroll_period_id' => $period?->id,
        ], $employee);

        return redirect()->route('employees.self-service')->with('success', 'Ta demande a été envoyée à la Direction. Tu verras ici quand elle sera acceptée ou refusée.');
    }

    public function index(User $employee): View
    {
        $this->authorizeAccess($employee);
        $employee->load('employeeProfile');

        $periods = EmployeePayrollPeriod::query()
            ->where('user_id', $employee->id)
            ->with(['payments.payer', 'staffAdvances.creator', 'staffAdvances.financeExpensePayment.expense', 'staffAdvances.employeePayrollPayment.payer'])
            ->orderByDesc('period_start')
            ->paginate(12)
            ->withQueryString();
        $periods->getCollection()->each(fn (EmployeePayrollPeriod $period) => $period->setAttribute('loan_deduction_due', $this->monthlyLoanDeductionDue($employee, $period)));
        $pendingAdvanceAmounts = FinanceExpenseRequest::query()
            ->whereIn('staff_payroll_period_id', $periods->getCollection()->pluck('id'))
            ->whereIn('status', ['awaiting_finance', 'awaiting_director', 'authorized_to_pay', 'partially_paid'])
            ->select('staff_payroll_period_id', DB::raw('SUM(amount - paid_amount) as pending_amount'))
            ->groupBy('staff_payroll_period_id')
            ->pluck('pending_amount', 'staff_payroll_period_id');
        $periods->getCollection()->each(function (EmployeePayrollPeriod $period) use ($pendingAdvanceAmounts): void {
            $period->setAttribute('pending_advance_amount', (int) ($pendingAdvanceAmounts[$period->id] ?? 0));
        });

        return view('employees.payroll.index', compact('employee', 'periods'));
    }

    public function createPeriod(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeAccess($employee);
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $profile = $employee->employeeProfile;
        if (! $profile || ! $profile->monthly_salary || ! $profile->salary_effective_from) {
            throw ValidationException::withMessages(['period' => 'Renseigne d’abord le salaire mensuel et sa date d’effet dans le dossier employé.']);
        }

        $periodStart = \Illuminate\Support\Carbon::createFromFormat('!Y-m', $data['period'])->startOfMonth();
        if ($periodStart->lt($profile->salary_effective_from->copy()->startOfMonth())) {
            throw ValidationException::withMessages(['period' => 'Le salaire convenu ne s’applique pas encore à ce mois.']);
        }
        if (EmployeePayrollPeriod::query()->where('user_id', $employee->id)->whereDate('period_start', $periodStart)->exists()) {
            throw ValidationException::withMessages(['period' => 'Le suivi de ce mois existe déjà pour cet employé.']);
        }

        $period = DB::transaction(function () use ($employee, $profile, $periodStart, $data, $request): EmployeePayrollPeriod {
            $period = EmployeePayrollPeriod::query()->create([
                'reference' => 'TMP-'.Str::uuid(),
                'user_id' => $employee->id,
                'period_start' => $periodStart->toDateString(),
                'salary_amount' => $profile->monthly_salary,
                'note' => $data['note'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            $period->update(['reference' => sprintf('SAL-%s-%06d', $periodStart->format('Y'), $period->id)]);

            FinanceAuditEvent::query()->create([
                'subject_type' => 'EmployeePayrollPeriod',
                'subject_id' => $period->id,
                'action' => 'salary_period_created',
                'after_values' => [
                    'reference' => $period->reference,
                    'employee' => $employee->name,
                    'period' => $periodStart->format('Y-m'),
                    'salary_amount' => $period->salary_amount,
                ],
                'reason' => $data['note'] ?? null,
                'user_id' => $request->user()->id,
                'created_at' => now(),
            ]);

            return $period;
        });

        return redirect()->route('employees.payroll.index', $employee->employeeProfile->tracking_id)->with('success', 'Le salaire du mois a été ajouté au suivi. Tu peux enregistrer un versement après remise effective des espèces.');
    }

    public function recordPayment(Request $request, User $employee, EmployeePayrollPeriod $period, FinanceCashLedgerService $ledger): RedirectResponse
    {
        $this->authorizeAccess($employee);
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless((int) $period->user_id === (int) $employee->id, 404);

        $data = $request->validate([
            'submission_key' => ['required', 'uuid'],
            'kind' => ['required', Rule::in(['salary'])],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($employee, $period, $data, $request, $ledger): void {
            $locked = EmployeePayrollPeriod::query()->lockForUpdate()->findOrFail($period->id);
            if ($locked->payments()->where('submission_key', $data['submission_key'])->exists()) {
                return;
            }
            $alreadyPaid = (int) $locked->payments()->where('kind', 'salary')->sum('amount');
            $alreadyAdvanced = (int) FinanceStaffAdvance::query()->where('payroll_period_id', $locked->id)->sum('amount');
            $pendingAdvances = (int) FinanceExpenseRequest::query()
                ->where('staff_payroll_period_id', $locked->id)
                ->whereIn('status', ['awaiting_finance', 'awaiting_director', 'authorized_to_pay', 'partially_paid'])
                ->sum(DB::raw('amount - paid_amount'));
            $remaining = max(0, (int) $locked->salary_amount - $alreadyPaid - $alreadyAdvanced - $pendingAdvances);
            if ((int) $data['amount'] > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Le versement dépasse le solde dû de '.number_format($remaining, 0, ',', ' ').' F CFA.',
                ]);
            }

            $paidAt = \Illuminate\Support\Carbon::parse($data['paid_at'])->startOfDay();
            $grossSalaryAmount = (int) $data['amount'];
            $remainingForDeduction = $grossSalaryAmount;
            $deductions = [];
            $monthlyLoans = FinanceStaffAdvance::query()
                ->where('user_id', $employee->id)
                ->where('advance_type', 'loan')
                ->whereIn('repayment_method', ['fixed_amount', 'salary_percent'])
                ->whereColumn('repaid_amount', '<', 'amount')
                ->orderBy('granted_on')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $monthStart = $locked->period_start->copy()->startOfMonth();
            $monthEnd = $monthStart->copy()->addMonth();
            foreach ($monthlyLoans as $loan) {
                if ($monthStart->lt($loan->granted_on->copy()->startOfMonth()) || $paidAt->lt($loan->granted_on->startOfDay())) {
                    continue;
                }
                $scheduled = $loan->repayment_method === 'salary_percent'
                    ? max(1, (int) round($locked->salary_amount * ((float) $loan->repayment_value / 100)))
                    : (int) $loan->repayment_value;
                $alreadyRepaidThisMonth = (int) $loan->repayments()
                    ->where('received_at', '>=', $monthStart)
                    ->where('received_at', '<', $monthEnd)
                    ->where('received_at', '<=', $paidAt->copy()->endOfDay())
                    ->sum('amount');
                $monthlyDue = max(0, $scheduled - $alreadyRepaidThisMonth);
                $outstanding = max(0, (int) $loan->amount - (int) $loan->repaid_amount);
                $deduction = min($monthlyDue, $outstanding, $remainingForDeduction);
                if ($deduction > 0) {
                    $deductions[] = ['advance' => $loan, 'amount' => $deduction];
                    $remainingForDeduction -= $deduction;
                }
                if ($remainingForDeduction <= 0) {
                    break;
                }
            }
            $deductionTotal = $grossSalaryAmount - $remainingForDeduction;
            $netSalaryAmount = $grossSalaryAmount - $deductionTotal;
            $payment = EmployeePayrollPayment::query()->create([
                'reference' => 'TMP-'.Str::uuid(),
                'submission_key' => $data['submission_key'],
                'period_id' => $locked->id,
                'kind' => $data['kind'],
                'amount' => $grossSalaryAmount,
                'net_amount' => $netSalaryAmount,
                'loan_deduction_amount' => $deductionTotal,
                'payment_reference' => $data['payment_reference'] ?? null,
                'paid_at' => $paidAt,
                'paid_by' => $request->user()->id,
            ]);
            $payment->update(['reference' => sprintf('PAI-%s-%06d', now()->format('Y'), $payment->id)]);

            if ($netSalaryAmount > 0) {
                $movement = $ledger->record([
                    'movement_type' => 'salary_payment',
                    'from_custody' => 'general_cash',
                    'to_custody' => 'external',
                    'amount' => $netSalaryAmount,
                    'source_type' => 'employee_payroll_payment',
                    'source_id' => $payment->id,
                    'source_key' => 'employee-payroll-payment:'.$payment->id,
                    'source_reference' => Str::limit($payment->reference.' · '.$employee->name.' · '.$locked->period_start->format('m/Y'), 120, ''),
                    'occurred_at' => $paidAt,
                    'description' => 'Salaire net remis à '.$employee->name.' pour '.$locked->period_start->translatedFormat('F Y').'.',
                    'created_by' => $request->user()->id,
                ]);
                $payment->update(['finance_cash_movement_id' => $movement->id]);
            }

            foreach ($deductions as $deduction) {
                /** @var FinanceStaffAdvance $loan */
                $loan = $deduction['advance'];
                $repayment = $loan->repayments()->create([
                    'employee_payroll_payment_id' => $payment->id,
                    'amount' => $deduction['amount'],
                    'received_at' => $paidAt,
                    'received_by' => $request->user()->id,
                ]);
                $loan->repaid_amount = (int) $loan->repaid_amount + $deduction['amount'];
                $loan->save();
                FinanceAuditEvent::query()->create([
                    'subject_type' => 'FinanceStaffAdvance',
                    'subject_id' => $loan->id,
                    'action' => 'staff_loan_salary_deduction',
                    'after_values' => [
                        'repayment_reference' => $payment->reference,
                        'employee' => $employee->name,
                        'period' => $locked->period_start->format('Y-m'),
                        'deducted_amount' => $deduction['amount'],
                        'repaid_amount' => $loan->repaid_amount,
                        'outstanding_amount' => max(0, (int) $loan->amount - (int) $loan->repaid_amount),
                    ],
                    'reason' => 'Retenue mensuelle automatique sur salaire.',
                    'user_id' => $request->user()->id,
                    'created_at' => now(),
                ]);
            }

            FinanceAuditEvent::query()->create([
                'subject_type' => 'EmployeePayrollPayment',
                'subject_id' => $payment->id,
                'action' => 'salary_payment_paid',
                'after_values' => [
                    'reference' => $payment->reference,
                    'employee' => $employee->name,
                    'period' => $locked->period_start->format('Y-m'),
                    'kind' => $payment->kind,
                    'amount' => $payment->amount,
                    'net_amount' => $payment->net_amount,
                    'loan_deduction_amount' => $payment->loan_deduction_amount,
                    'remaining_after_payment' => $remaining - $payment->amount,
                ],
                'reason' => $payment->payment_reference,
                'user_id' => $request->user()->id,
                'created_at' => now(),
            ]);
        });

        return redirect()->route('employees.payroll.index', $employee->employeeProfile->tracking_id)->with('success', 'Le salaire a été enregistré. Les retenues de prêt ont été déduites automatiquement et seul le montant net a été retiré de la caisse.');
    }

    public function voucher(Request $request, EmployeePayrollPayment $payment): View
    {
        $this->authorizeAccess($payment->period->employee);
        $payment->load(['period.employee.employeeProfile', 'payer']);

        return view('employees.payroll.voucher', compact('payment'));
    }

    private function authorizeAccess(User $employee): void
    {
        abort_unless($employee->isAdmin() || $this->isEmployeeRole($employee), 404);
        abort_unless(auth()->user()?->isAdmin() || (int) auth()->id() === (int) $employee->id, 403);
    }

    private function isEmployeeRole(User $user): bool
    {
        return in_array($user->user_type, [
            User::ROLE_CALL_CENTER, User::ROLE_SALES, User::ROLE_MANAGER, User::ROLE_SECRETARY,
            User::ROLE_LIVREUR, User::ROLE_CASHIER, User::ROLE_ACCOUNTANT, User::ROLE_COMMERCIAL_MANAGER,
            User::ROLE_WHOLESALER, User::ROLE_IT,
        ], true);
    }

    private function monthlyLoanDeductionDue(User $employee, EmployeePayrollPeriod $period): int
    {
        $monthStart = $period->period_start->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->addMonth();
        $total = 0;
        $loans = FinanceStaffAdvance::query()->where('user_id', $employee->id)
            ->where('advance_type', 'loan')->whereIn('repayment_method', ['fixed_amount', 'salary_percent'])
            ->whereColumn('repaid_amount', '<', 'amount')->orderBy('granted_on')->orderBy('id')->get();
        foreach ($loans as $loan) {
            if ($monthStart->lt($loan->granted_on->copy()->startOfMonth())) {
                continue;
            }
            $planned = $loan->repayment_method === 'salary_percent'
                ? max(1, (int) round($period->salary_amount * ((float) $loan->repayment_value / 100)))
                : (int) $loan->repayment_value;
            $paidThisMonth = (int) $loan->repayments()->where('received_at', '>=', $monthStart)
                ->where('received_at', '<', $monthEnd)->sum('amount');
            $total += min(max(0, $planned - $paidThisMonth), max(0, (int) $loan->amount - (int) $loan->repaid_amount));
        }

        return min($total, (int) $period->salary_amount);
    }
}
