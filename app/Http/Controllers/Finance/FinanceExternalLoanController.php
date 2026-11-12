<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceExternalLoan;
use App\Models\FinanceExternalLoanRepayment;
use App\Models\User;
use App\Services\FinanceAuditService;
use App\Services\FinanceCashLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceExternalLoanController extends Controller
{
    public function index(Request $request, FinanceCashLedgerService $ledger): View
    {
        $canRepay = $this->authorizeViewer($request->user());
        $loans = FinanceExternalLoan::query()->with('creator')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByRaw('next_payment_on IS NULL')
            ->orderBy('next_payment_on')
            ->latest('id')->paginate(15)->withQueryString();
        $activeLoans = FinanceExternalLoan::query()->where('status', 'active');

        return view('finance.loans.index', [
            'loans' => $loans,
            'canRepay' => $canRepay,
            'generalCash' => $ledger->balance('general_cash'),
            'loanCount' => (clone $activeLoans)->count(),
            'totalReceived' => FinanceExternalLoan::query()->sum('received_amount'),
            'totalRepayable' => FinanceExternalLoan::query()->sum('total_repayable'),
            'totalRepaid' => FinanceExternalLoan::query()->sum('amount_repaid'),
            'remaining' => (int) (clone $activeLoans)->sum(DB::raw('total_repayable - amount_repaid')),
            'nextPayment' => (clone $activeLoans)->whereNotNull('next_payment_on')->min('next_payment_on'),
        ]);
    }

    public function create(Request $request, FinanceCashLedgerService $ledger): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('finance.loans.create', ['generalCash' => $ledger->balance('general_cash')]);
    }

    public function store(Request $request, FinanceCashLedgerService $ledger, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'lender_name' => ['required', 'string', 'max:160'],
            'contract_reference' => ['nullable', 'string', 'max:100'],
            'received_amount' => ['required', 'integer', 'min:1'],
            'total_repayable' => ['required', 'integer', 'gte:received_amount'],
            'initial_repaid_amount' => ['nullable', 'integer', 'min:0', 'lte:total_repayable'],
            'received_on' => ['required', 'date', 'before_or_equal:today'],
            'repayment_frequency' => ['nullable', 'in:weekly,monthly'],
            'first_payment_on' => ['nullable', 'required_with:repayment_frequency', 'date', 'after_or_equal:received_on'],
            'installment_amount' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
        $creditGeneralCash = $request->boolean('credit_general_cash');

        $loan = DB::transaction(function () use ($data, $creditGeneralCash, $request, $ledger, $audit): FinanceExternalLoan {
            $alreadyRepaid = (int) ($data['initial_repaid_amount'] ?? 0);
            $loan = FinanceExternalLoan::query()->create(array_merge($data, [
                'reference' => 'TMP-'.Str::uuid(),
                'initial_repaid_amount' => $alreadyRepaid,
                'amount_repaid' => $alreadyRepaid,
                'status' => $alreadyRepaid >= (int) $data['total_repayable'] ? 'closed' : 'active',
                'repayment_frequency' => $data['repayment_frequency'] ?? null,
                'first_payment_on' => $data['first_payment_on'] ?? null,
                'next_payment_on' => $alreadyRepaid >= (int) $data['total_repayable'] ? null : ($data['first_payment_on'] ?? null),
                'created_by' => $request->user()->id,
            ]));
            $loan->update(['reference' => sprintf('EMP-%s-%06d', now()->format('Y'), $loan->id)]);

            if ($creditGeneralCash) {
                $cashMovement = $ledger->record([
                    'movement_type' => 'external_loan_received',
                    'from_custody' => 'external',
                    'to_custody' => 'general_cash',
                    'amount' => (int) $loan->received_amount,
                    'source_type' => 'finance_external_loan',
                    'source_id' => $loan->id,
                    'source_key' => 'external-loan:'.$loan->id.':received',
                    'source_reference' => $loan->reference.' · '.Str::limit($loan->lender_name, 90),
                    'occurred_at' => now(),
                    'description' => 'Fonds du prêt reçu de '.$loan->lender_name.'. Montant total à rembourser : '.number_format((int) $loan->total_repayable, 0, ',', ' ').' F.',
                    'created_by' => $request->user()->id,
                ]);
                $loan->update(['cash_movement_id' => $cashMovement->id]);
            }

            $audit->record($loan, 'external_loan_received', null, $loan->only([
                'reference', 'lender_name', 'contract_reference', 'received_amount', 'total_repayable', 'initial_repaid_amount', 'received_on', 'repayment_frequency', 'first_payment_on', 'next_payment_on', 'cash_movement_id',
            ]));

            return $loan;
        });

        $message = $creditGeneralCash
            ? 'Emprunt enregistré et montant reçu ajouté à la caisse générale.'
            : 'Emprunt existant enregistré sans mouvement de caisse pour éviter de compter deux fois les fonds déjà reçus.';

        return redirect()->route('finance.loans.show', $loan)->with('success', $message);
    }

    public function show(Request $request, FinanceExternalLoan $loan, FinanceCashLedgerService $ledger): View
    {
        $canRepay = $this->authorizeViewer($request->user());
        $loan->load(['creator', 'cashMovement', 'repayments.creator']);
        $generalCash = $ledger->balance('general_cash');
        $suggestedNextPaymentOn = $this->nextScheduledPaymentDate($loan, today()->toDateString());

        return view('finance.loans.show', compact('loan', 'canRepay', 'generalCash', 'suggestedNextPaymentOn'));
    }

    public function repay(Request $request, FinanceExternalLoan $loan, FinanceCashLedgerService $ledger, FinanceAuditService $audit): RedirectResponse
    {
        abort_unless($this->authorizeViewer($request->user()), 403);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'next_payment_on' => ['nullable', 'date', 'after_or_equal:paid_on'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($loan, $data, $request, $ledger, $audit): void {
            $locked = FinanceExternalLoan::query()->lockForUpdate()->findOrFail($loan->id);
            abort_unless($locked->status === 'active', 409, 'Cet emprunt est déjà soldé.');
            $remaining = (int) $locked->total_repayable - (int) $locked->amount_repaid;
            if ($data['amount'] > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Le remboursement dépasse le solde restant de '.number_format($remaining, 0, ',', ' ').' F.',
                ]);
            }

            $nextPaymentOn = $data['next_payment_on'] ?? $this->nextScheduledPaymentDate($locked, $data['paid_on']);
            $repayment = FinanceExternalLoanRepayment::query()->create([
                'loan_id' => $locked->id,
                'amount' => $data['amount'],
                'paid_on' => $data['paid_on'],
                'next_payment_on' => $nextPaymentOn,
                'note' => $data['note'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            $cashMovement = $ledger->record([
                'movement_type' => 'external_loan_repayment',
                'from_custody' => 'general_cash',
                'to_custody' => 'external',
                'amount' => $data['amount'],
                'source_type' => 'finance_external_loan_repayment',
                'source_id' => $repayment->id,
                'source_key' => 'external-loan-repayment:'.$repayment->id,
                'source_reference' => $locked->reference.' · '.Str::limit($locked->lender_name, 90),
                'occurred_at' => now(),
                'description' => 'Remboursement du prêt '.$locked->reference.' auprès de '.$locked->lender_name.'.',
                'created_by' => $request->user()->id,
            ]);
            $repayment->update(['cash_movement_id' => $cashMovement->id]);

            $locked->amount_repaid = (int) $locked->amount_repaid + (int) $data['amount'];
            $locked->status = $locked->amount_repaid >= (int) $locked->total_repayable ? 'closed' : 'active';
            $locked->next_payment_on = $locked->status === 'closed' ? null : $nextPaymentOn;
            $locked->save();

            $audit->record($locked, 'external_loan_repayment', null, [
                'repayment_id' => $repayment->id,
                'amount_paid_now' => (int) $data['amount'],
                'amount_repaid' => (int) $locked->amount_repaid,
                'remaining_amount' => max(0, (int) $locked->total_repayable - (int) $locked->amount_repaid),
                'next_payment_on' => $locked->next_payment_on?->toDateString(),
            ]);
        });

        return back()->with('success', 'Remboursement enregistré et déduit de la caisse générale.');
    }

    private function authorizeViewer(User $user): bool
    {
        abort_unless($user->isAdmin(), 403);

        return true;
    }

    private function nextScheduledPaymentDate(FinanceExternalLoan $loan, string $paidOn): ?string
    {
        if (! $loan->next_payment_on) {
            return null;
        }

        $next = $loan->next_payment_on->copy();
        if (! in_array($loan->repayment_frequency, ['weekly', 'monthly'], true)) {
            return $next->toDateString();
        }

        while ($next->lte($paidOn)) {
            $next = $loan->repayment_frequency === 'weekly'
                ? $next->addWeek()
                : $next->addMonthNoOverflow();
        }

        return $next->toDateString();
    }
}
