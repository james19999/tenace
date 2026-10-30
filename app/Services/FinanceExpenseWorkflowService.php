<?php

namespace App\Services;

use App\Models\FinanceExpenseApproval;
use App\Models\FinanceExpensePayment;
use App\Models\FinanceExpenseRequest;
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

    public function submit(array $data, User $requester): FinanceExpenseRequest
    {
        if ($requester->isManager() && empty($data['stock_location_id'])) {
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

        return DB::transaction(function () use ($data, $requester): FinanceExpenseRequest {
            $category = ! empty($data['category_id'])
                ? TypeExpensive::query()->findOrFail($data['category_id'])
                : TypeExpensive::query()->firstOrCreate(['name' => trim((string) $data['category_name'])]);
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
                'status' => 'awaiting_finance',
                'approval_route' => (int) $data['amount'] > self::DIRECTOR_APPROVAL_THRESHOLD ? 'finance_and_director' : 'finance',
                'justification_path' => $data['justification_path'] ?? null,
                'justification_note' => $data['justification_note'] ?? null,
                'requested_by' => $requester->id,
                'submitted_at' => now(),
            ]);
            $expense->update(['reference' => sprintf('DEP-%s-%06d', now()->format('Y'), $expense->id)]);
            $this->audit->record($expense, 'submitted', null, $expense->fresh()->toArray());

            Notification::send(
                User::query()->where('user_type', User::ROLE_ACCOUNTANT)->where('active', true)->where('id', '<>', $requester->id)->get(),
                new FinanceWorkflowNotification(
                    'Dépense à contrôler',
                    'Une demande de dépense attend le contrôle financier : '.$expense->reference.'.',
                    route('finance.expenses.show', $expense->id),
                    'request_quote',
                ),
            );

            return $expense->fresh();
        });
    }

    public function financeDecision(FinanceExpenseRequest $expense, User $financeUser, string $decision, ?string $comment): FinanceExpenseRequest
    {
        abort_unless(in_array($decision, ['approved', 'rejected'], true), 422);
        abort_unless($financeUser->isAccountant(), 403);
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
                ? ((int) $locked->amount > self::DIRECTOR_APPROVAL_THRESHOLD ? 'awaiting_director' : 'authorized_to_pay')
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
            $locked->requester?->notify(new FinanceWorkflowNotification(
                $decision === 'approved' ? 'Contrôle financier effectué' : 'Demande de dépense rejetée',
                $decision === 'approved'
                    ? ((int) $locked->amount > self::DIRECTOR_APPROVAL_THRESHOLD ? 'Le contrôle financier est terminé. La décision du DG est maintenant attendue.' : 'Le responsable financier a autorisé la dépense. Le DG en a été informé.')
                    : 'Le responsable financier a rejeté la demande '.$locked->reference.'.',
                route('finance.expenses.show', $locked->id),
                'request_quote',
            ));

            return $locked->fresh();
        });
    }

    public function directorDecision(FinanceExpenseRequest $expense, User $director, string $decision, ?string $comment): FinanceExpenseRequest
    {
        abort_unless(in_array($decision, ['approved', 'rejected'], true), 422);
        abort_unless($director->isAdmin(), 403);
        abort_if((int) $expense->requested_by === (int) $director->id, 403, 'Tu ne peux pas approuver ta propre demande.');
        if ((int) $expense->amount <= self::DIRECTOR_APPROVAL_THRESHOLD) {
            throw ValidationException::withMessages(['expense' => 'Les dépenses de 50 000 F ou moins suivent le circuit financier standard. Le DG est informé de la décision.']);
        }
        if (! $expense->finance_reviewed_at || $expense->status !== 'awaiting_director') {
            throw ValidationException::withMessages(['expense' => 'Le contrôle financier doit être enregistré avant la décision du DG pour ce montant.']);
        }
        abort_if($expense->status === 'rejected', 409);

        return DB::transaction(function () use ($expense, $director, $decision, $comment): FinanceExpenseRequest {
            $locked = FinanceExpenseRequest::query()->lockForUpdate()->findOrFail($expense->id);
            abort_unless((int) $locked->amount > self::DIRECTOR_APPROVAL_THRESHOLD
                && $locked->status === 'awaiting_director'
                && $locked->finance_reviewed_at, 409);
            $before = $locked->only(['status', 'director_decided_by', 'director_decided_at']);
            $locked->director_decided_by = $director->id;
            $locked->director_decided_at = now();
            $locked->director_comment = $comment;
            if ($decision === 'rejected') {
                $locked->status = 'rejected';
            } elseif (! $locked->finance_reviewed_at) {
                throw ValidationException::withMessages(['expense' => 'Le contrôle financier manque pour cette dépense.']);
            } else {
                $locked->status = 'authorized_to_pay';
            }
            $locked->save();

            FinanceExpenseApproval::query()->create([
                'expense_request_id' => $locked->id, 'stage' => 'director', 'decision' => $decision,
                'decided_by' => $director->id, 'comment' => $comment, 'decided_at' => now(),
            ]);
            $this->audit->record($locked, $decision === 'approved' ? 'director_approved' : 'director_rejected', $before, $locked->only(['status', 'director_decided_by', 'director_decided_at']), $comment);
            $locked->requester?->notify(new FinanceWorkflowNotification(
                $decision === 'approved' ? 'Dépense autorisée par le DG' : 'Demande de dépense rejetée par le DG',
                'La décision du DG concernant '.$locked->reference.' a été enregistrée.'.($comment ? ' Motif : '.$comment : ''),
                route('finance.expenses.show', $locked->id),
                'request_quote',
            ));

            return $locked->fresh();
        });
    }

    public function pay(FinanceExpenseRequest $expense, User $payer, int $amount, string $custody, ?int $locationId, ?string $reference, ?string $proofPath): FinanceExpensePayment
    {
        abort_unless(in_array($expense->status, ['authorized_to_pay', 'partially_paid'], true), 409);
        if ($amount <= 0 || (int) $expense->paid_amount + $amount > (int) $expense->amount) {
            throw ValidationException::withMessages(['amount' => 'Le paiement dépasse le reste autorisé de la dépense.']);
        }
        if ($custody === 'shop') {
            abort_unless($locationId && (int) $expense->stock_location_id === $locationId, 422, 'La boutique de paiement doit être celle de la demande.');
            if (! $payer->isAdmin() && (int) StockLocation::query()->whereKey($locationId)->value('responsible_user_id') !== (int) $payer->id) {
                abort(403);
            }
        } elseif ($custody === 'general_cash') {
            abort_unless($payer->isAdmin() || $payer->isAccountant() || $payer->hasRole(User::ROLE_CASHIER), 403);
        } else {
            abort(422);
        }

        return DB::transaction(function () use ($expense, $payer, $amount, $custody, $locationId, $reference, $proofPath): FinanceExpensePayment {
            $locked = FinanceExpenseRequest::query()->lockForUpdate()->findOrFail($expense->id);
            abort_unless(in_array($locked->status, ['authorized_to_pay', 'partially_paid'], true), 409);
            if ((int) $locked->paid_amount + $amount > (int) $locked->amount) {
                throw ValidationException::withMessages(['amount' => 'Le paiement dépasse le reste autorisé de la dépense.']);
            }

            $payment = FinanceExpensePayment::query()->create([
                'expense_request_id' => $locked->id, 'amount' => $amount, 'custody_account' => $custody,
                'stock_location_id' => $custody === 'shop' ? $locationId : null,
                'payment_reference' => $reference, 'proof_path' => $proofPath,
                'paid_by' => $payer->id, 'paid_at' => now(),
            ]);
            $this->ledger->record([
                'movement_type' => 'expense_payment', 'from_custody' => $custody, 'to_custody' => 'external',
                'amount' => $amount, 'stock_location_id' => $custody === 'shop' ? $locationId : null,
                'source_type' => 'expense_payment', 'source_id' => $payment->id,
                'source_key' => 'expense-payment:'.$payment->id,
                'source_reference' => $locked->reference, 'occurred_at' => now(),
                'description' => 'Paiement de la dépense '.$locked->reference.' au bénéficiaire '.$locked->beneficiary.'.',
                'created_by' => $payer->id,
            ]);

            $locked->paid_amount = (int) $locked->paid_amount + $amount;
            $locked->payment_status = $locked->paid_amount >= $locked->amount ? 'paid' : 'partial';
            $locked->paid_at = now();
            $locked->paid_by = $payer->id;
            $locked->status = $locked->payment_status === 'paid' ? 'paid' : 'partially_paid';
            $locked->save();
            $this->audit->record($locked, 'payment_recorded', null, ['amount' => $amount, 'custody' => $custody, 'paid_amount' => $locked->paid_amount]);
            $locked->requester?->notify(new FinanceWorkflowNotification(
                'Paiement de dépense enregistré',
                'Un paiement de '.number_format($amount, 0, ',', ' ').' F a été enregistré pour '.$locked->reference.'.',
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
}
