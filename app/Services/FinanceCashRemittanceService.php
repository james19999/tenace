<?php

namespace App\Services;

use App\Models\FinanceCashRemittance;
use App\Models\StockLocation;
use App\Models\User;
use App\Notifications\FinanceWorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinanceCashRemittanceService
{
    public function __construct(
        private readonly FinanceCashLedgerService $ledger,
        private readonly FinanceAuditService $audit,
    ) {
    }

    public function prepare(array $data, User $manager): FinanceCashRemittance
    {
        return DB::transaction(function () use ($data, $manager): FinanceCashRemittance {
            $location = StockLocation::query()->whereKey($data['stock_location_id'])->where('type', 'shop')->where('is_active', true)->firstOrFail();
            if ((int) $location->responsible_user_id !== (int) $manager->id) {
                abort(403);
            }

            $collector = User::query()->whereKey($data['collector_id'])->where('active', true)->firstOrFail();
            if ($collector->user_type === User::ROLE_PARTNER) {
                throw ValidationException::withMessages(['collector_id' => 'Choisis un compte utilisateur TENACOS actif pour garder une signature nominative et traçable.']);
            }
            if ($collector->id === $manager->id) {
                throw ValidationException::withMessages(['collector_id' => 'Le responsable de boutique et la personne qui récupère les fonds doivent confirmer avec leurs comptes respectifs.']);
            }

            $expected = max(0, $this->ledger->expectedShopCash($location->id, $data['period_start'], $data['period_end']));
            if ($expected !== (int) $data['counted_amount'] && ! trim((string) ($data['discrepancy_note'] ?? ''))) {
                $counted = (int) $data['counted_amount'];
                $difference = abs($expected - $counted);
                $kind = $counted < $expected ? 'manque' : 'excédent';
                $format = static fn (int $amount): string => number_format($amount, 0, ',', ' ');
                throw ValidationException::withMessages([
                    'discrepancy_note' => sprintf(
                        'Écart à expliquer : attendu %s F, compté %s F, %s de %s F.',
                        $format($expected), $format($counted), $kind, $format($difference),
                    ),
                ]);
            }
            $remittance = FinanceCashRemittance::query()->create([
                'reference' => 'TMP-'.Str::uuid(),
                'stock_location_id' => $location->id,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'expected_amount' => $expected,
                'counted_amount' => $data['counted_amount'],
                'handed_over_amount' => $data['counted_amount'],
                'destination' => $data['destination'],
                'status' => 'awaiting_collector',
                'shop_manager_id' => $manager->id,
                'shop_manager_signed_at' => now(),
                'collector_id' => $collector->id,
                'discrepancy_note' => $expected !== (int) $data['counted_amount'] ? ($data['discrepancy_note'] ?? null) : null,
                'paper_voucher_reference' => $data['paper_voucher_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $manager->id,
            ]);
            $remittance->update(['reference' => sprintf('REM-%s-%06d', now()->format('Y'), $remittance->id)]);
            $this->audit->record($remittance, 'prepared_and_shop_signed', null, $remittance->fresh()->toArray());
            $collector->notify(new FinanceWorkflowNotification(
                'Remise à récupérer',
                'Une remise de '.$location->name.' attend ta confirmation : '.$remittance->reference.'.',
                route('finance.remittances.show', $remittance->id),
                'payments',
            ));

            return $remittance->fresh();
        });
    }

    public function collectorSign(FinanceCashRemittance $remittance, User $collector, ?string $signedVoucherPath = null, ?string $bankSlipPath = null, ?string $bankSlipReference = null): FinanceCashRemittance
    {
        return DB::transaction(function () use ($remittance, $collector, $signedVoucherPath, $bankSlipPath, $bankSlipReference): FinanceCashRemittance {
            $locked = FinanceCashRemittance::query()->lockForUpdate()->findOrFail($remittance->id);
            abort_unless($locked->status === 'awaiting_collector' && (int) $locked->collector_id === (int) $collector->id, 403);
            if ($locked->destination === 'bank_deposit' && (! $bankSlipPath || ! trim((string) $bankSlipReference))) {
                throw ValidationException::withMessages(['bank_slip' => 'Ajoute le bordereau et sa référence avant de confirmer le dépôt bancaire.']);
            }

            $locked->collector_signed_at = now();
            $locked->signed_voucher_path = $signedVoucherPath ?: $locked->signed_voucher_path;
            $locked->bank_slip_path = $bankSlipPath ?: $locked->bank_slip_path;
            $locked->bank_slip_reference = $bankSlipReference ?: $locked->bank_slip_reference;
            $locked->status = 'in_transit';
            $locked->save();
            $shopBalance = $this->ledger->balance('shop', $locked->stock_location_id);
            if ($locked->handed_over_amount > $shopBalance) {
                $overage = $locked->handed_over_amount - $shopBalance;
                if (! trim((string) $locked->discrepancy_note)) {
                    throw ValidationException::withMessages(['discrepancy_note' => 'Explique le montant excédentaire avant de signer la collecte.']);
                }
                $this->ledger->record([
                    'movement_type' => 'cash_count_overage', 'from_custody' => 'external', 'to_custody' => 'shop',
                    'amount' => $overage, 'stock_location_id' => $locked->stock_location_id, 'remittance_id' => $locked->id,
                    'source_type' => 'cash_remittance', 'source_id' => $locked->id,
                    'source_key' => 'remittance:'.$locked->id.':count-overage', 'source_reference' => $locked->reference,
                    'occurred_at' => now(), 'description' => 'Écart positif de comptage déclaré sur le bon : '.$locked->discrepancy_note,
                    'created_by' => $collector->id,
                ]);
            }
            $this->ledger->record([
                'movement_type' => 'remittance_handover', 'from_custody' => 'shop', 'to_custody' => 'transit',
                'amount' => $locked->handed_over_amount, 'stock_location_id' => $locked->stock_location_id,
                'remittance_id' => $locked->id, 'source_type' => 'cash_remittance', 'source_id' => $locked->id,
                'source_key' => 'remittance:'.$locked->id.':handover', 'source_reference' => $locked->reference,
                'occurred_at' => now(), 'description' => 'Remise de fonds récupérée auprès de la boutique.',
                'created_by' => $collector->id,
            ]);
            $this->audit->record($locked, 'collector_signed_handover', null, ['collector_id' => $collector->id, 'amount' => $locked->handed_over_amount]);
            Notification::send(
                User::query()->where('user_type', User::ROLE_ADMIN)->where('active', true)->get(),
                new FinanceWorkflowNotification(
                    'Remise à confirmer',
                    'La remise '.$locked->reference.' est en transit et attend la confirmation du DG.',
                    route('finance.remittances.show', $locked->id),
                    'approval',
                ),
            );

            return $locked->fresh();
        });
    }

    public function confirmReceipt(FinanceCashRemittance $remittance, User $director, int $receivedAmount, ?string $bankReference = null, ?string $discrepancyNote = null, ?string $bankSlipPath = null): FinanceCashRemittance
    {
        abort_unless($director->isAdmin(), 403);

        return DB::transaction(function () use ($remittance, $director, $receivedAmount, $bankReference, $discrepancyNote, $bankSlipPath): FinanceCashRemittance {
            $locked = FinanceCashRemittance::query()->lockForUpdate()->findOrFail($remittance->id);
            abort_unless($locked->status === 'in_transit', 409);
            if ($locked->destination === 'bank_deposit' && ((! $bankSlipPath && ! $locked->bank_slip_path) || ! trim((string) $bankReference))) {
                throw ValidationException::withMessages(['bank_slip_reference' => 'Le bordereau bancaire doit être archivé et vérifié avant la confirmation du DG.']);
            }
            $transitBalance = $this->ledger->balance('transit', $locked->stock_location_id, $locked->id);
            if ($receivedAmount > $transitBalance) {
                throw ValidationException::withMessages(['received_amount' => 'Le montant reçu ne peut pas dépasser le montant remis par la boutique.']);
            }
            if ($receivedAmount > 0) {
                $this->ledger->record([
                    'movement_type' => $locked->destination === 'bank_deposit' ? 'bank_deposit_confirmed' : 'central_cash_received',
                    'from_custody' => 'transit', 'to_custody' => 'general_cash', 'amount' => $receivedAmount,
                    'stock_location_id' => $locked->stock_location_id, 'remittance_id' => $locked->id,
                    'source_type' => 'cash_remittance', 'source_id' => $locked->id,
                    'source_key' => 'remittance:'.$locked->id.':receipt', 'source_reference' => $locked->reference,
                    'occurred_at' => now(),
                    'description' => $locked->destination === 'bank_deposit' ? 'Dépôt bancaire confirmé par le DG, enregistré dans la caisse générale.' : 'Fonds reçus et confirmés par le DG.',
                    'metadata' => ['destination' => $locked->destination, 'bank_slip_reference' => $bankReference],
                    'created_by' => $director->id,
                ]);
            }

            $hasDifference = $receivedAmount !== (int) $locked->handed_over_amount
                || (int) $locked->expected_amount !== (int) $locked->counted_amount;
            if ($hasDifference && ! trim((string) $discrepancyNote) && ! trim((string) $locked->discrepancy_note)) {
                throw ValidationException::withMessages(['discrepancy_note' => 'Explique l’écart avant de confirmer la réception.']);
            }

            $locked->received_amount = $receivedAmount;
            $locked->received_by = $director->id;
            $locked->received_at = now();
            $locked->bank_slip_reference = $bankReference ?: $locked->bank_slip_reference;
            $locked->bank_slip_path = $bankSlipPath ?: $locked->bank_slip_path;
            $locked->discrepancy_note = $discrepancyNote ?: $locked->discrepancy_note;
            $locked->status = $hasDifference ? 'discrepancy_open' : 'received';
            $locked->save();
            $this->audit->record($locked, 'receipt_confirmed', null, ['received_amount' => $receivedAmount, 'status' => $locked->status]);
            $message = $hasDifference
                ? 'La remise '.$locked->reference.' présente un écart et demande une résolution.'
                : 'La réception de la remise '.$locked->reference.' a été confirmée par le DG.';
            Notification::send(
                User::query()->whereIn('id', array_filter([$locked->shop_manager_id, $locked->collector_id]))->get(),
                new FinanceWorkflowNotification(
                    $hasDifference ? 'Écart sur une remise' : 'Remise reçue',
                    $message,
                    route('finance.remittances.show', $locked->id),
                    $hasDifference ? 'warning' : 'task_alt',
                ),
            );

            return $locked->fresh();
        });
    }

    public function resolveDiscrepancy(FinanceCashRemittance $remittance, User $director, string $note): FinanceCashRemittance
    {
        abort_unless($director->isAdmin(), 403);
        abort_unless($remittance->status === 'discrepancy_open', 409);

        return DB::transaction(function () use ($remittance, $director, $note): FinanceCashRemittance {
            $locked = FinanceCashRemittance::query()->lockForUpdate()->findOrFail($remittance->id);
            abort_unless($locked->status === 'discrepancy_open', 409);
            if ((int) $locked->expected_amount > (int) $locked->counted_amount) {
                $shortage = (int) $locked->expected_amount - (int) $locked->counted_amount;
                $this->ledger->record([
                    'movement_type' => 'cash_shortage_approved', 'from_custody' => 'shop', 'to_custody' => 'external',
                    'amount' => $shortage, 'stock_location_id' => $locked->stock_location_id, 'remittance_id' => $locked->id,
                    'source_type' => 'cash_remittance', 'source_id' => $locked->id,
                    'source_key' => 'remittance:'.$locked->id.':shop-shortage', 'source_reference' => $locked->reference,
                    'occurred_at' => now(), 'description' => 'Écart de caisse boutique validé par le DG : '.$note,
                    'created_by' => $director->id,
                ]);
            }
            if ((int) $locked->handed_over_amount > (int) $locked->received_amount) {
                $missing = (int) $locked->handed_over_amount - (int) $locked->received_amount;
                $this->ledger->record([
                    'movement_type' => 'transit_shortage_approved', 'from_custody' => 'transit', 'to_custody' => 'external',
                    'amount' => $missing, 'stock_location_id' => $locked->stock_location_id, 'remittance_id' => $locked->id,
                    'source_type' => 'cash_remittance', 'source_id' => $locked->id,
                    'source_key' => 'remittance:'.$locked->id.':transit-shortage', 'source_reference' => $locked->reference,
                    'occurred_at' => now(), 'description' => 'Écart de transport validé par le DG : '.$note,
                    'created_by' => $director->id,
                ]);
            }
            $before = $locked->only(['status', 'discrepancy_note', 'resolution_note']);
            $locked->update([
                'status' => 'received', 'resolution_note' => $note,
                'resolved_by' => $director->id, 'resolved_at' => now(),
            ]);
            $this->audit->record($locked, 'discrepancy_resolved', $before, $locked->fresh()->only(['status', 'resolution_note', 'resolved_by', 'resolved_at']), $note);
            Notification::send(
                User::query()->whereIn('id', array_filter([$locked->shop_manager_id, $locked->collector_id]))->get(),
                new FinanceWorkflowNotification(
                    'Écart de remise traité',
                    'Le DG a clôturé l’écart de la remise '.$locked->reference.'.',
                    route('finance.remittances.show', $locked->id),
                    'task_alt',
                ),
            );

            return $locked->fresh();
        });
    }
}
