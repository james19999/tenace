<?php

namespace App\Http\Controllers;

use App\Models\EmployeePayrollPayment;
use App\Models\EmployeePayrollPeriod;
use App\Models\FinanceAuditEvent;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceStaffAdvance;
use App\Models\User;
use App\Services\FinanceCashLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeePayrollController extends Controller
{
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
            $payment = EmployeePayrollPayment::query()->create([
                'reference' => 'TMP-'.Str::uuid(),
                'submission_key' => $data['submission_key'],
                'period_id' => $locked->id,
                'kind' => $data['kind'],
                'amount' => $data['amount'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'paid_at' => $paidAt,
                'paid_by' => $request->user()->id,
            ]);
            $payment->update(['reference' => sprintf('PAI-%s-%06d', now()->format('Y'), $payment->id)]);

            $movement = $ledger->record([
                'movement_type' => 'salary_payment',
                'from_custody' => 'general_cash',
                'to_custody' => 'external',
                'amount' => $payment->amount,
                'source_type' => 'employee_payroll_payment',
                'source_id' => $payment->id,
                'source_key' => 'employee-payroll-payment:'.$payment->id,
                'source_reference' => Str::limit($payment->reference.' · '.$employee->name.' · '.$locked->period_start->format('m/Y'), 120, ''),
                'occurred_at' => $paidAt,
                'description' => 'Versement de salaire remis à '.$employee->name.' pour '.$locked->period_start->translatedFormat('F Y').'.',
                'created_by' => $request->user()->id,
            ]);
            $payment->update(['finance_cash_movement_id' => $movement->id]);

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
                    'remaining_after_payment' => $remaining - $payment->amount,
                ],
                'reason' => $payment->payment_reference,
                'user_id' => $request->user()->id,
                'created_at' => now(),
            ]);
        });

        return redirect()->route('employees.payroll.index', $employee->employeeProfile->tracking_id)->with('success', 'Le versement a été enregistré, la caisse générale a été débitée et le bon est disponible.');
    }

    public function voucher(Request $request, EmployeePayrollPayment $payment): View
    {
        $this->authorizeAccess($payment->period->employee);
        $payment->load(['period.employee.employeeProfile', 'payer']);

        return view('employees.payroll.voucher', compact('payment'));
    }

    private function authorizeAccess(User $employee): void
    {
        abort_unless($employee->isAdmin()
            || in_array($employee->user_type, [
                User::ROLE_CALL_CENTER, User::ROLE_SALES, User::ROLE_MANAGER, User::ROLE_SECRETARY,
                User::ROLE_LIVREUR, User::ROLE_CASHIER, User::ROLE_ACCOUNTANT, User::ROLE_COMMERCIAL_MANAGER,
            ], true), 404);
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
