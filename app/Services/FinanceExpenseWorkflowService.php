<?php

namespace App\Services;

use App\Models\FinanceExpenseApproval;
use App\Models\FinanceExpensePayment;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceGeneralCashSetting;
use App\Models\StockLocation;
use App\Models\TypeExpensive;
use App\Models\User;
use App\Notifications\FinanceExpenseReportNotification;
use App\Notifications\FinanceWorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinanceExpenseWorkflowService
{
    private const DIRECTOR_APPROVAL_THRESHOLD = 50000;

    public function __construct(
        private readonly FinanceCashLedgerService $ledger,
        private readonly FinanceAuditService $audit,
    ) {
    }

    public function submit(array $data, User $requester, bool $directlyAuthorizedByDirector = false, bool $notifyCashier = true, bool $requiresDirectorReview = false): FinanceExpenseRequest
    {
        abort_if($directlyAuthorizedByDirector && ! $requester->isAdmin(), 403);
        abort_if($requiresDirectorReview && ! $requester->isAdmin(), 403);
        if ($requester->isManager() && empty($data['stock_location_id']) && empty($data['staff_user_id'])) {
            throw ValidationException::withMessages(['stock_location_id' => 'Une demande du responsable de boutique doit être imputée à sa boutique.']);
        }
        if (empty($data['justification_path']) && ! trim((string) ($data['justification_note'] ?? ''))) {
            throw ValidationException::withMessages(['justification_note' => 'Ajoute un justificatif ou explique pourquoi il n’est pas disponible.']);
        }
        if (! empty($data['stock_location_id'])) {
            $location = StockLocation::query()->whereKey($data['stock_location_id'])->where('type', 'shop')->where('is_active', true)->firstOrFail();
            if (! $requester->isAdmin() && (int) $location->responsible_user_id !== (int) $requester->id) {
                abort(403);
            }
        }

        $accountantRequestNeedsDirector = $requester->isAccountant() && empty($data['staff_advance_type']);

        return DB::transaction(function () use ($data, $requester, $directlyAuthorizedByDirector, $notifyCashier, $requiresDirectorReview, $accountantRequestNeedsDirector): FinanceExpenseRequest {
            $category = ! empty($data['category_id'])
                ? TypeExpensive::query()->findOrFail($data['category_id'])
                : (! empty($data['staff_advance_type']) || ! empty($data['commission_user_id'])
                    ? TypeExpensive::query()->firstOrCreate(['name' => trim((string) $data['category_name'])])
                    : throw ValidationException::withMessages(['category_id' => 'Choisis une catégorie de dépense existante.']));
            $status = $directlyAuthorizedByDirector
                ? 'authorized_to_pay'
                : (($requiresDirectorReview || $accountantRequestNeedsDirector) ? 'awaiting_director' : 'awaiting_finance');
            $approvalRoute = $directlyAuthorizedByDirector || $requiresDirectorReview
                ? 'director_direct'
                : ($accountantRequestNeedsDirector
                    ? 'accountant_director'
                    : ((int) $data['amount'] > self::DIRECTOR_APPROVAL_THRESHOLD ? 'finance_and_director' : 'finance'));
            $expense = FinanceExpenseRequest::query()->create([
                'reference' => 'TMP-'.Str::uuid(),
                'stock_location_id' => $data['stock_location_id'] ?? null,
                'category_id' => $category->id,
                'category_name' => $category->name,
                'description' => $data['description'],
                'amount' => $data['amount'],
                'beneficiary' => $data['beneficiary'],
                'budget_reference' => $data['budget_reference'] ?? null,
                'requested_payment_date' => $data['requested_payment_date'] ?? null,
                'status' => $status,
                'approval_route' => $approvalRoute,
                'justification_path' => $data['justification_path'] ?? null,
                'justification_note' => $data['justification_note'] ?? null,
                'requested_by' => $requester->id,
                'staff_user_id' => $data['staff_user_id'] ?? null,
                'staff_advance_type' => $data['staff_advance_type'] ?? null,
                'staff_payroll_period_id' => $data['staff_payroll_period_id'] ?? null,
                'staff_repayment_method' => $data['staff_repayment_method'] ?? null,
                'staff_repayment_value' => $data['staff_repayment_value'] ?? null,
                'commission_user_id' => $data['commission_user_id'] ?? null,
                'commission_period_start' => $data['commission_period_start'] ?? null,
                'commission_period_end' => $data['commission_period_end'] ?? null,
                'submitted_at' => now(),
                'director_decided_by' => $directlyAuthorizedByDirector ? $requester->id : null,
                'director_decided_at' => $directlyAuthorizedByDirector ? now() : null,
                'director_comment' => $directlyAuthorizedByDirector ? 'Autorisation directe du DG.' : null,
            ]);
            $expense->update(['reference' => sprintf('DEP-%s-%06d', now()->format('Y'), $expense->id)]);
            if ($directlyAuthorizedByDirector) {
                FinanceExpenseApproval::query()->create([
                    'expense_request_id' => $expense->id,
                    'stage' => 'director',
                    'decision' => 'approved',
                    'decided_by' => $requester->id,
                    'comment' => 'Autorisation directe du DG.',
                    'decided_at' => now(),
                ]);
                if ($notifyCashier) {
                    $this->notifyGeneralCashier($expense);
                }
            }
            $this->audit->record($expense, $directlyAuthorizedByDirector ? 'director_authorized_at_creation' : ($requiresDirectorReview ? 'director_direct_expense_submitted' : 'submitted'), null, $expense->fresh()->toArray());

            if ($requiresDirectorReview) {
                $requester->notify(new FinanceWorkflowNotification(
                    'Dépense directe à vérifier',
                    'Le caissier a enregistré la dépense '.$expense->reference.' à ta demande. Vérifie les détails et autorise-la avant tout décaissement.',
                    route('finance.expenses.show', $expense->id),
                    'request_quote',
                ));
            } elseif (! $directlyAuthorizedByDirector) {
                $reviewers = (! empty($data['staff_advance_type']) || $accountantRequestNeedsDirector)
                    ? User::query()->where('user_type', User::ROLE_ADMIN)
                    : User::query()->where('user_type', User::ROLE_ACCOUNTANT);
                Notification::send(
                    $reviewers->where('active', true)->where('id', '<>', $requester->id)->get(),
                    new FinanceWorkflowNotification(
                        ! empty($data['staff_advance_type']) ? 'Demande d’avance d’un employé' : ($accountantRequestNeedsDirector ? 'Dépense du comptable à valider' : 'Dépense à contrôler'),
                        ! empty($data['staff_advance_type'])
                            ? 'Un employé demande une avance sur salaire : '.$expense->reference.'.'
                            : ($accountantRequestNeedsDirector
                                ? 'Une demande de dépense déposée par le comptable attend la décision du DG : '.$expense->reference.'.'
                                : 'Une demande de dépense attend le contrôle financier : '.$expense->reference.'.'),
                        ! empty($data['staff_advance_type']) ? route('finance.management.index', ['tab' => 'team']) : route('finance.expenses.show', $expense->id),
                        'request_quote',
                    ),
                );
            }

            return $expense->fresh();
        });
    }

    public function financeDecision(FinanceExpenseRequest $expense, User $financeUser, string $decision, ?string $comment): FinanceExpenseRequest
    {
        abort_unless(in_array($decision, ['approved', 'rejected'], true), 422);
        abort_unless($financeUser->isAccountant(), 403);
        if ($decision === 'rejected' && ! trim((string) $comment)) {
            throw ValidationException::withMessages(['comment' => 'Indique le motif du refus pour informer le demandeur.']);
        }
        abort_if((int) $expense->requested_by === (int) $financeUser->id, 403, 'Tu ne peux pas approuver ta propre demande.');
        abort_unless($expense->status === 'awaiting_finance', 409);

        return DB::transaction(function () use ($expense, $financeUser, $decision, $comment): FinanceExpenseRequest {
            $locked = FinanceExpenseRequest::query()->lockForUpdate()->findOrFail($expense->id);
            abort_unless($locked->status === 'awaiting_finance', 409);
            $before = $locked->only(['status', 'finance_reviewed_by', 'finance_reviewed_at']);
            $locked->finance_reviewed_by = $financeUser->id;
            $locked->finance_reviewed_at = now();
            $locked->finance_comment = $comment;
            $locked->status = $decision === 'approved'
                ? (! empty($locked->staff_advance_type) || (int) $locked->amount > self::DIRECTOR_APPROVAL_THRESHOLD ? 'awaiting_director' : 'authorized_to_pay')
                : 'rejected';
            $locked->save();

            FinanceExpenseApproval::query()->create([
                'expense_request_id' => $locked->id, 'stage' => 'finance', 'decision' => $decision,
                'decided_by' => $financeUser->id, 'comment' => $comment, 'decided_at' => now(),
            ]);
            $this->audit->record($locked, $decision === 'approved' ? 'finance_review_approved' : 'finance_review_rejected', $before, $locked->only(['status', 'finance_reviewed_by', 'finance_reviewed_at']), $comment);

            $message = $decision === 'rejected'
                ? 'Le responsable financier a rejeté une demande de dépense. Son motif est enregistré.'
                : ((int) $locked->amount > self::DIRECTOR_APPROVAL_THRESHOLD
                    ? 'Une dépense de plus de 50 000 F attend la décision du DG.'
                    : 'Le responsable financier a autorisé une dépense. Elle est signalée au DG.');
            $this->notifyDirectors($locked, $message);
            if ($locked->status === 'authorized_to_pay') {
                $this->notifyGeneralCashier($locked);
            }
            $requesterMessage = $decision === 'approved'
                ? ((int) $locked->amount > self::DIRECTOR_APPROVAL_THRESHOLD
                    ? 'Le contrôle financier est terminé. La décision du DG est maintenant attendue.'
                    : 'Le responsable financier a autorisé la dépense. Le DG en a été informé.')
                : 'Le responsable financier a refusé la demande '.$locked->reference.'. Motif : '.trim((string) $comment);
            $locked->requester?->notify(new FinanceWorkflowNotification(
                $decision === 'approved' ? 'Contrôle financier effectué' : 'Demande de dépense rejetée',
                $requesterMessage,
                route('finance.expenses.show', $locked->id),
                'request_quote',
            ));
            if ($locked->status === 'authorized_to_pay') {
                $this->notifyGeneralCashier($locked);
            }

            return $locked->fresh();
        });
    }

    public function directorDecision(FinanceExpenseRequest $expense, User $director, string $decision, ?string $comment): FinanceExpenseRequest
    {
        abort_unless(in_array($decision, ['approved', 'rejected'], true), 422);
        abort_unless($director->isAdmin(), 403);
        if ($decision === 'rejected' && ! trim((string) $comment)) {
            throw ValidationException::withMessages(['comment' => 'Indique le motif du refus pour informer le demandeur.']);
        }
        $directorOnlyRequest = in_array($expense->approval_route, ['accountant_director', 'director_direct'], true);
        if ($expense->approval_route === 'director_direct') {
            abort_unless((int) $expense->requested_by === (int) $director->id, 403, 'Seul le DG indiqué sur la demande peut la vérifier.');
        }
        abort_if((int) $expense->requested_by === (int) $director->id && $expense->approval_route !== 'director_direct', 403, 'Tu ne peux pas approuver ta propre demande.');
        if ((! $directorOnlyRequest && ! $expense->finance_reviewed_at) || $expense->status !== 'awaiting_director') {
            throw ValidationException::withMessages(['expense' => 'La demande doit être en attente de la décision du DG avant validation.']);
        }

        return DB::transaction(function () use ($expense, $director, $decision, $comment): FinanceExpenseRequest {
            $locked = FinanceExpenseRequest::query()->lockForUpdate()->findOrFail($expense->id);
            $directorOnlyRequest = in_array($locked->approval_route, ['accountant_director', 'director_direct'], true);
            if ($locked->approval_route === 'director_direct') {
                abort_unless((int) $locked->requested_by === (int) $director->id, 403, 'Seul le DG indiqué sur la demande peut la vérifier.');
            }
            abort_if((int) $locked->requested_by === (int) $director->id && $locked->approval_route !== 'director_direct', 403, 'Tu ne peux pas approuver ta propre demande.');
            abort_unless($locked->status === 'awaiting_director'
                && ($directorOnlyRequest || ((int) $locked->amount > self::DIRECTOR_APPROVAL_THRESHOLD && $locked->finance_reviewed_at)), 409);
            $before = $locked->only(['status', 'director_decided_by', 'director_decided_at']);
            $locked->director_decided_by = $director->id;
            $locked->director_decided_at = now();
            $locked->director_comment = $comment;
            if ($decision === 'rejected') {
                $locked->status = 'rejected';
            } else {
                $locked->status = 'authorized_to_pay';
            }
            $locked->save();

            FinanceExpenseApproval::query()->create([
                'expense_request_id' => $locked->id, 'stage' => 'director', 'decision' => $decision,
                'decided_by' => $director->id, 'comment' => $comment, 'decided_at' => now(),
            ]);
            $this->audit->record($locked, $decision === 'approved' ? 'director_approved' : 'director_rejected', $before, $locked->only(['status', 'director_decided_by', 'director_decided_at']), $comment);
            $requesterMessage = $decision === 'rejected'
                ? 'Le DG a refusé la demande '.$locked->reference.'. Motif : '.trim((string) $comment)
                : 'Le DG a autorisé la demande '.$locked->reference.'.'.($comment ? ' Commentaire : '.$comment : '');
            $locked->requester?->notify(new FinanceWorkflowNotification(
                $decision === 'approved' ? 'Dépense autorisée par le DG' : 'Demande de dépense rejetée par le DG',
                $requesterMessage,
                route('finance.expenses.show', $locked->id),
                'request_quote',
            ));
            if ($locked->status === 'authorized_to_pay') {
                $this->notifyGeneralCashier($locked);
            }

            return $locked->fresh();
        });
    }

    public function pay(FinanceExpenseRequest $expense, User $payer, int $amount, ?string $reference): FinanceExpensePayment
    {
        $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
        abort_unless($cashierId && (int) $cashierId === (int) $payer->id, 403,
            'Seul le responsable désigné de la caisse générale peut remettre les fonds.');
        abort_if((int) $expense->requested_by === (int) $payer->id, 403, 'Le responsable de caisse ne peut pas décaisser sa propre demande.');
        abort_unless(in_array($expense->status, ['authorized_to_pay', 'partially_paid'], true), 409);
        if ($amount <= 0 || (int) $expense->paid_amount + $amount > (int) $expense->amount) {
            throw ValidationException::withMessages(['amount' => 'Le paiement dépasse le reste autorisé de la dépense.']);
        }
        if ($expense->staff_advance_type === 'loan' && $amount !== (int) $expense->amount - (int) $expense->paid_amount) {
            throw ValidationException::withMessages(['amount' => 'Un prêt doit être remis en une seule fois pour conserver un seul plan de remboursement mensuel.']);
        }
        return DB::transaction(function () use ($expense, $payer, $amount, $reference): FinanceExpensePayment {
            $locked = FinanceExpenseRequest::query()->lockForUpdate()->findOrFail($expense->id);
            abort_unless(in_array($locked->status, ['authorized_to_pay', 'partially_paid'], true), 409);
            if ((int) $locked->paid_amount + $amount > (int) $locked->amount) {
                throw ValidationException::withMessages(['amount' => 'Le paiement dépasse le reste autorisé de la dépense.']);
            }
            if ($locked->staff_advance_type === 'loan' && $amount !== (int) $locked->amount - (int) $locked->paid_amount) {
                throw ValidationException::withMessages(['amount' => 'Un prêt doit être remis en une seule fois pour conserver un seul plan de remboursement mensuel.']);
            }

            $payrollPeriod = null;
            if ($locked->staff_user_id && $locked->staff_advance_type === 'salary_advance') {
                $payrollPeriod = \App\Models\EmployeePayrollPeriod::query()
                    ->where('user_id', $locked->staff_user_id)
                    ->lockForUpdate()
                    ->findOrFail($locked->staff_payroll_period_id);
                $alreadyPaid = (int) $payrollPeriod->payments()->sum('amount');
                $alreadyAdvanced = (int) \App\Models\FinanceStaffAdvance::query()
                    ->where('payroll_period_id', $payrollPeriod->id)
                    ->sum('amount');
                $pendingAdvances = (int) FinanceExpenseRequest::query()
                    ->where('staff_payroll_period_id', $payrollPeriod->id)
                    ->where('id', '<>', $locked->id)
                    ->whereIn('status', ['awaiting_finance', 'awaiting_director', 'authorized_to_pay', 'partially_paid'])
                    ->sum(DB::raw('amount - paid_amount'));
                $currentRequestRemaining = max(0, (int) $locked->amount - (int) $locked->paid_amount);
                if ($alreadyPaid + $alreadyAdvanced + $pendingAdvances + $currentRequestRemaining > (int) $payrollPeriod->salary_amount) {
                    throw ValidationException::withMessages(['amount' => 'Le paiement dépasserait le salaire restant de ce mois. Vérifie les avances et demandes en cours.']);
                }
            }

            $payment = FinanceExpensePayment::query()->create([
                'expense_request_id' => $locked->id, 'amount' => $amount, 'custody_account' => 'general_cash',
                'stock_location_id' => null,
                'payment_reference' => $reference, 'proof_path' => null,
                'paid_by' => $payer->id, 'paid_at' => now(),
            ]);
            $isStaffLoan = $locked->staff_user_id && $locked->staff_advance_type === 'loan';
            $this->ledger->record([
                'movement_type' => $isStaffLoan ? 'staff_loan_disbursed' : 'expense_payment', 'from_custody' => 'general_cash', 'to_custody' => 'external',
                'amount' => $amount, 'stock_location_id' => null,
                'source_type' => $isStaffLoan ? 'staff_loan_disbursement' : 'expense_payment', 'source_id' => $payment->id,
                'source_key' => ($isStaffLoan ? 'staff-loan-disbursement:' : 'expense-payment:').$payment->id,
                'source_reference' => $locked->reference, 'occurred_at' => now(),
                'description' => $isStaffLoan
                    ? 'Prêt accordé à '.$locked->beneficiary.' · '.$locked->reference.'.'
                    : 'Paiement de la dépense '.$locked->reference.' au bénéficiaire '.$locked->beneficiary.'.',
                'created_by' => $payer->id,
            ]);

            $locked->paid_amount = (int) $locked->paid_amount + $amount;
            $locked->payment_status = $locked->paid_amount >= $locked->amount ? 'paid' : 'partial';
            $locked->paid_at = now();
            $locked->paid_by = $payer->id;
            $locked->status = $locked->payment_status === 'paid' ? 'paid' : 'partially_paid';
            $locked->save();
            if ($locked->staff_user_id) {
                $advance = \App\Models\FinanceStaffAdvance::query()->create([
                    'reference' => 'TMP-'.Str::uuid(), 'user_id' => $locked->staff_user_id,
                    'finance_expense_payment_id' => $payment->id, 'amount' => $amount,
                    'advance_type' => $locked->staff_advance_type ?: 'loan',
                    'repayment_method' => $locked->staff_repayment_method,
                    'repayment_value' => $locked->staff_repayment_value,
                    'payroll_period_id' => $payrollPeriod?->id,
                    'granted_on' => $payment->paid_at->toDateString(), 'reason' => $locked->description,
                    'created_by' => $payer->id,
                ]);
                $advance->update(['reference' => sprintf('AV-%s-%06d', now()->format('Y'), $advance->id)]);
                $this->audit->record($advance, 'staff_advance_disbursed', null, $advance->fresh()->toArray());
            }
            if ($locked->commission_user_id) {
                $settlement = \App\Models\FinanceCommissionSettlement::query()->create([
                    'reference' => 'TMP-'.Str::uuid(), 'user_id' => $locked->commission_user_id,
                    'amount' => $amount, 'period_start' => $locked->commission_period_start,
                    'period_end' => $locked->commission_period_end, 'paid_at' => $payment->paid_at,
                    'paid_by' => $payer->id,
                ]);
                $settlement->update(['reference' => sprintf('COM-%s-%06d', now()->format('Y'), $settlement->id)]);
                $this->audit->record($settlement, 'commission_settled', null, $settlement->fresh()->toArray());
            }
            $this->audit->record($locked, 'payment_recorded', null, ['amount' => $amount, 'custody' => 'general_cash', 'paid_amount' => $locked->paid_amount]);
            $locked->requester?->notify(new FinanceWorkflowNotification(
                'Fonds remis depuis la caisse générale',
                number_format($amount, 0, ',', ' ').' F ont été remis pour '.$locked->reference.'. Après l’achat, ajoute les reçus dans le dossier.',
                route('finance.expenses.show', $locked->id),
                'payments',
            ));

            return $payment;
        });
    }

    private function notifyDirectors(FinanceExpenseRequest $expense, string $message): void
    {
        $directors = User::query()->where('user_type', User::ROLE_ADMIN)->where('active', true)->get();
        Notification::send($directors, new FinanceExpenseReportNotification($expense, $message));
    }

    private function notifyGeneralCashier(FinanceExpenseRequest $expense): void
    {
        $cashierId = FinanceGeneralCashSetting::query()->whereKey(1)->value('cashier_user_id');
        $cashier = $cashierId
            ? User::query()->where('active', true)->find($cashierId)
            : null;
        if (! $cashier || (int) $cashier->id === (int) $expense->requested_by) {
            return;
        }

        $cashier->notify(new FinanceWorkflowNotification(
            'Dépense autorisée à remettre',
            'La dépense '.$expense->reference.' est validée. Prépare la remise depuis la caisse générale.',
            route('finance.expenses.show', $expense->id),
            'payments',
        ));
    }
}
