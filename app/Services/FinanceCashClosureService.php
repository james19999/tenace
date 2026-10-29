<?php

namespace App\Services;

use App\Models\FinanceCashClosure;
use App\Models\FinanceCashMovement;
use App\Models\User;
use App\Notifications\FinanceCashCountAlertNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class FinanceCashClosureService
{
    public function __construct(private readonly FinanceAuditService $audit)
    {
    }

    public function count(string $date, int $countedAmount, User $counter, ?array $denominations, ?string $note): FinanceCashClosure
    {
        abort_unless($counter->isAdmin() || $counter->hasRole(User::ROLE_CASHIER), 403);
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
                'status' => 'awaiting_review',
                'count_note' => $note,
                'counted_by' => $counter->id,
                'counted_at' => now(),
                'resolution_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ];
            if ($existing) {
                $before = $existing->toArray();
                $existing->update($values);
                $closure = $existing->fresh();
                $this->audit->record($closure, 'cash_count_resubmitted_after_reopening', $before, $closure->toArray(), $note);
            } else {
                $closure = FinanceCashClosure::query()->create($values);
                $this->audit->record($closure, 'cash_count_submitted', null, $closure->toArray(), $note);
            }
            if ($variance !== 0) {
                Notification::send(
                    User::query()->where('user_type', User::ROLE_ADMIN)->where('active', true)->get(),
                    new FinanceCashCountAlertNotification($closure)
                );
            }

            return $closure;
        });
    }

    public function review(FinanceCashClosure $closure, User $reviewer, string $decision, ?string $resolutionNote = null): FinanceCashClosure
    {
        abort_unless($reviewer->isAdmin() || $reviewer->isAccountant(), 403);
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
            $this->audit->record($locked, $decision === 'approved' ? 'cash_closure_approved' : 'cash_closure_reopened', null, $locked->fresh()->toArray(), $resolutionNote);

            return $locked->fresh();
        });
    }

    private function centralBalance(string $date): int
    {
        return (int) FinanceCashMovement::query()->where('occurred_at', '<=', $date.' 23:59:59')->selectRaw(
            "COALESCE(SUM(CASE WHEN to_custody = 'general_cash' THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN from_custody = 'general_cash' THEN amount ELSE 0 END), 0) as balance"
        )->value('balance');
    }
}
