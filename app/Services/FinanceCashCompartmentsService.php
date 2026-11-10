<?php

namespace App\Services;

use App\Models\FinanceCashCompartment;
use App\Models\FinanceCashCompartmentEntry;
use App\Models\FinanceCashMovement;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceExpensePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinanceCashCompartmentsService
{
    public function recordMovement(FinanceCashMovement $movement, ?string $compartmentKey = null): void
    {
        if ($movement->to_custody === 'general_cash' && $movement->from_custody !== 'general_cash') {
            $this->allocateReceipt($movement);
            return;
        }

        if ($movement->from_custody !== 'general_cash' || $movement->to_custody === 'general_cash') {
            return;
        }

        $key = $compartmentKey ?: $this->compartmentForOutflow($movement);
        if (! $key) {
            return; // The payment remains visible against the unallocated balance.
        }

        $compartment = FinanceCashCompartment::query()->where('key', $key)->first();
        if (! $compartment) {
            throw ValidationException::withMessages(['compartment_id' => 'Le compartiment financier choisi est introuvable.']);
        }

        FinanceCashCompartmentEntry::query()->firstOrCreate(
            ['source_key' => 'movement:'.$movement->id.':outflow'],
            [
                'compartment_id' => $compartment->id,
                'cash_movement_id' => $movement->id,
                'entry_type' => 'expense',
                'amount' => $movement->amount,
                'description' => $movement->description,
                'occurred_at' => $movement->occurred_at,
                'created_by' => $movement->created_by,
            ],
        );
    }

    public function updatePercentages(array $percentages, int $actorId): int
    {
        return DB::transaction(function () use ($percentages, $actorId): int {
            $compartments = FinanceCashCompartment::query()->orderBy('id')->lockForUpdate()->get();
            if (count($percentages) !== $compartments->count()
                || $compartments->contains(fn ($compartment) => ! array_key_exists((string) $compartment->id, $percentages))) {
                throw ValidationException::withMessages(['percentages' => 'Renseigne la part de chacun des huit compartiments.']);
            }

            $sum = array_sum(array_map('intval', $percentages));
            if ($sum !== 100) {
                throw ValidationException::withMessages(['percentages' => 'La répartition doit totaliser exactement 100 %. Total saisi : '.$sum.' %.']);
            }

            foreach ($compartments as $compartment) {
                $compartment->percentage = (int) $percentages[$compartment->id];
                $compartment->save();
            }

            // First configuration (and later unassigned cash) distributes the real cash already
            // present in the general cashier. Existing compartment balances are never reshuffled.
            $distributed = $this->unallocatedBalance(app(FinanceCashLedgerService::class));
            if ($distributed > 0) {
                $this->allocateAmount($compartments, $distributed, 'opening-distribution:'.Str::uuid(), now(), $actorId,
                    null, 'Répartition du solde non affecté de la caisse générale');
            }

            return $distributed;
        });
    }

    public function summary(FinanceCashLedgerService $ledger): array
    {
        $compartments = FinanceCashCompartment::query()->withSum([
            'entries as allocated_total' => fn ($query) => $query->where('entry_type', 'allocation'),
            'entries as spent_total' => fn ($query) => $query->where('entry_type', 'expense'),
        ], 'amount')->orderBy('id')->get();

        $compartments->each(function ($compartment): void {
            $compartment->balance = (int) $compartment->allocated_total - (int) $compartment->spent_total;
            $compartment->utilization = (int) $compartment->allocated_total > 0
                ? (int) round(((int) $compartment->spent_total / (int) $compartment->allocated_total) * 100)
                : ((int) $compartment->spent_total > 0 ? 100 : 0);
        });

        $pendingByCompartment = FinanceExpenseRequest::query()
            ->whereIn('status', ['awaiting_finance', 'awaiting_director', 'authorized_to_pay', 'partially_paid'])
            ->select('compartment_id')->selectRaw('SUM(amount - paid_amount) as pending_amount')
            ->groupBy('compartment_id')->pluck('pending_amount', 'compartment_id');
        $compartments->each(fn ($compartment) => $compartment->pending_amount = (int) $pendingByCompartment->get($compartment->id, 0));

        return [
            'compartments' => $compartments,
            'cash_balance' => $ledger->balance('general_cash'),
            'allocated_balance' => (int) $compartments->sum('balance'),
            'unallocated_balance' => max(0, $ledger->balance('general_cash') - (int) $compartments->sum('balance')),
            'percentages_configured' => (int) $compartments->sum('percentage') === 100,
        ];
    }

    public function unallocatedBalance(FinanceCashLedgerService $ledger): int
    {
        $spent = (int) FinanceCashCompartmentEntry::query()->where('entry_type', 'expense')->sum('amount');
        $allocated = (int) FinanceCashCompartmentEntry::query()->where('entry_type', 'allocation')->sum('amount');
        return max(0, $ledger->balance('general_cash') - ($allocated - $spent));
    }

    private function allocateReceipt(FinanceCashMovement $movement): void
    {
        $compartments = FinanceCashCompartment::query()->orderBy('id')->lockForUpdate()->get();
        if ((int) $compartments->sum('percentage') !== 100) {
            return;
        }
        $this->allocateAmount($compartments, (int) $movement->amount, 'movement:'.$movement->id.':allocation',
            $movement->occurred_at, $movement->created_by, $movement, 'Répartition automatique · '.$movement->source_reference);
    }

    private function allocateAmount($compartments, int $amount, string $keyPrefix, $occurredAt, ?int $createdBy, ?FinanceCashMovement $movement, string $description): void
    {
        $portions = [];
        $remainders = [];
        $remainder = $amount;
        foreach ($compartments as $compartment) {
            $weightedAmount = $amount * (int) $compartment->percentage;
            $portion = intdiv($weightedAmount, 100);
            $portions[] = [$compartment, $portion];
            if ((int) $compartment->percentage > 0) {
                $remainders[] = ['index' => count($portions) - 1, 'fraction' => $weightedAmount % 100, 'id' => $compartment->id];
            }
            $remainder -= $portion;
        }
        usort($remainders, fn ($left, $right) => [$right['fraction'], $left['id']] <=> [$left['fraction'], $right['id']]);
        for ($index = 0; $index < $remainder && isset($remainders[$index]); $index++) {
            $portions[$remainders[$index]['index']][1]++;
        }

        foreach ($portions as [$compartment, $portion]) {
            if ($portion <= 0) {
                continue;
            }
            FinanceCashCompartmentEntry::query()->firstOrCreate(
                ['source_key' => $keyPrefix.':'.$compartment->id],
                [
                    'compartment_id' => $compartment->id,
                    'cash_movement_id' => $movement?->id,
                    'entry_type' => 'allocation',
                    'amount' => $portion,
                    'percentage_snapshot' => $compartment->percentage,
                    'description' => $description,
                    'occurred_at' => $occurredAt,
                    'created_by' => $createdBy,
                ],
            );
        }
    }

    private function compartmentForOutflow(FinanceCashMovement $movement): ?string
    {
        return match ($movement->movement_type) {
            'salary_payment', 'tontine_recovery' => 'exploitation',
            'tontine_contribution' => 'reserves_benefices',
            'supplier_payment' => 'approvisionnement',
            'equipment_purchase_advance' => 'approvisionnement',
            'cash_shortage_approved' => 'reserves_benefices',
            'expense_payment', 'staff_loan_disbursed' => $this->expensePaymentCompartment($movement),
            default => null,
        };
    }

    private function expensePaymentCompartment(FinanceCashMovement $movement): ?string
    {
        $payment = FinanceExpensePayment::query()->with('expense.compartment')->find($movement->source_id);
        return $payment?->expense?->compartment?->key ?? 'exploitation';
    }
}
