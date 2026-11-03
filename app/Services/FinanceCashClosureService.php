<?php

namespace App\Services;

use App\Models\FinanceCashClosure;
use App\Models\FinanceCashMovement;
use App\Models\User;
use App\Notifications\FinanceWorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceCashClosureService
{
    public function __construct(
        private readonly FinanceAuditService $audit,
        private readonly FinanceCashLedgerService $ledger,
    )
    {
    }

    public function count(string $date, int $countedAmount, User $counter, ?array $denominations, ?string $note): FinanceCashClosure
    {
        abort_unless($counter->isAdmin(), 403);
        if ($date > now()->toDateString()) {
            throw ValidationException::withMessages(['closing_date' => 'La date de clôture ne peut pas être dans le futur.']);
        }
        return DB::transaction(function () use ($date, $countedAmount, $counter, $denominations, $note): FinanceCashClosure {
            $existing = FinanceCashClosure::query()->whereDate('closing_date', $date)->lockForUpdate()->first();
            if ($existing && $existing->status !== 'reopened') {
                throw ValidationException::withMessages(['closing_date' => 'Un comptage a déjà été soumis pour cette date. Toute réouverture doit être autorisée et historisée.']);
            }
            $theoretical = $this->centralBalance($date);
            $variance = $countedAmount - $theoretical;
            if ($variance !== 0 && ! trim((string) $note)) {
                throw ValidationException::withMessages(['count_note' => 'Explique l’écart entre le solde théorique et le comptage physique.']);
            }

            $values = [
                'closing_date' => $date,
                'theoretical_amount' => $theoretical,
                'counted_amount' => $countedAmount,
                'variance_amount' => $variance,
                'denominations' => $denominations,
                'status' => 'closed',
                'count_note' => $note,
                'counted_by' => $counter->id,
                'counted_at' => now(),
                'resolution_note' => $note,
                'reviewed_by' => $counter->id,
                'reviewed_at' => now(),
            ];
            if ($existing) {
                $before = $existing->toArray();
                $existing->update($values);
                $closure = $existing->fresh();
                $this->audit->record($closure, 'cash_count_validated_after_reopening', $before, $closure->toArray(), $note);
            } else {
                $closure = FinanceCashClosure::query()->create($values);
                $this->audit->record($closure, 'cash_count_validated_at_entry', null, $closure->toArray(), $note);
            }
            $this->applyVariance($closure, $counter, $note);

            return $closure->fresh();
        });
    }

    public function review(FinanceCashClosure $closure, User $reviewer, string $decision, ?string $resolutionNote = null): FinanceCashClosure
    {
        abort_unless($reviewer->isAdmin(), 403);
        abort_unless(in_array($decision, ['approved', 'reopened'], true), 422);

        return DB::transaction(function () use ($closure, $reviewer, $decision, $resolutionNote): FinanceCashClosure {
            $locked = FinanceCashClosure::query()->lockForUpdate()->findOrFail($closure->id);
            if ($decision === 'reopened') {
                abort_unless($reviewer->isAdmin(), 403);
                abort_unless($locked->status === 'closed', 409);
                if (! trim((string) $resolutionNote)) {
                    throw ValidationException::withMessages(['resolution_note' => 'Le motif de réouverture est obligatoire.']);
                }
                $locked->status = 'reopened';
            } else {
                abort_unless($locked->status === 'awaiting_review', 409);
                abort_if((int) $locked->counted_by === (int) $reviewer->id, 403, 'La personne qui a compté la caisse ne peut pas valider sa propre clôture.');
                if ((int) $locked->variance_amount !== 0 && ! trim((string) $resolutionNote)) {
                    throw ValidationException::withMessages(['resolution_note' => 'Explique et valide l’écart avant de clôturer la caisse.']);
                }
                $locked->status = 'closed';
            }
            $locked->reviewed_by = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->resolution_note = $resolutionNote;
            $locked->save();
            if ($decision === 'approved') {
                $this->applyVariance($locked, $reviewer, $resolutionNote ?: $locked->count_note);
            }
            $this->audit->record($locked, $decision === 'approved' ? 'cash_closure_approved' : 'cash_closure_reopened', null, $locked->fresh()->toArray(), $resolutionNote);
            User::query()->find($locked->counted_by)?->notify(new FinanceWorkflowNotification(
                $decision === 'approved' ? 'Comptage de caisse validé' : 'Caisse rouverte pour correction',
                $decision === 'approved'
                    ? 'Le comptage de la caisse générale du '.$locked->closing_date->format('d/m/Y').' a été validé.'
                    : 'Le DG a rouvert le comptage du '.$locked->closing_date->format('d/m/Y').' pour correction.',
                route('finance.dashboard'),
                $decision === 'approved' ? 'task_alt' : 'edit',
            ));

            return $locked->fresh();
        });
    }

    private function centralBalance(string $date): int
    {
        return (int) FinanceCashMovement::query()->where('occurred_at', '<=', $date.' 23:59:59')->selectRaw(
            "COALESCE(SUM(CASE WHEN to_custody = 'general_cash' THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN from_custody = 'general_cash' THEN amount ELSE 0 END), 0) as balance"
        )->value('balance');
    }

    private function applyVariance(FinanceCashClosure $closure, User $director, ?string $note): void
    {
        $variance = (int) $closure->variance_amount;
        if ($variance === 0) {
            return;
        }

        $isOverage = $variance > 0;
        $this->ledger->record([
            'movement_type' => $isOverage ? 'cash_count_overage' : 'cash_shortage_approved',
            'from_custody' => $isOverage ? 'external' : 'general_cash',
            'to_custody' => $isOverage ? 'general_cash' : 'external',
            'amount' => abs($variance),
            'source_type' => 'finance_cash_closure',
            'source_id' => $closure->id,
            'source_key' => 'cash-closure:'.$closure->id.':count:'.$closure->counted_at->timestamp,
            'source_reference' => 'Comptage caisse générale du '.$closure->closing_date->format('d/m/Y'),
            'occurred_at' => now(),
            'description' => ($isOverage ? 'Surplus' : 'Manquant').' de caisse confirmé par le DG. Comptage : '
                .number_format((int) $closure->counted_amount, 0, ',', ' ')
                .' F ; solde théorique : '.number_format((int) $closure->theoretical_amount, 0, ',', ' ')
                .' F. '.trim((string) $note),
            'metadata' => [
                'closure_id' => $closure->id,
                'closing_date' => $closure->closing_date->toDateString(),
                'counted_amount' => (int) $closure->counted_amount,
                'theoretical_amount' => (int) $closure->theoretical_amount,
                'variance_amount' => $variance,
                'counted_by' => (int) $closure->counted_by,
                'reviewed_by' => (int) $director->id,
            ],
            'created_by' => $director->id,
        ]);
    }
}
