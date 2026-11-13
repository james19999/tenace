<?php

namespace App\Console\Commands;

use App\Models\FinanceCashMovement;
use App\Models\FinanceCashRemittance;
use App\Models\StockLocation;
use App\Services\FinanceCashLedgerService;
use App\Services\StockModuleSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileWholesaleRemittances extends Command
{
    protected $signature = 'finance:reconcile-wholesale-remittances {--apply : Enregistre les sorties de caisse grossiste manquantes}';

    protected $description = 'Vérifie et, avec --apply, régularise les remises grossistes confirmées non débitées de leur caisse.';

    public function handle(FinanceCashLedgerService $ledger, StockModuleSettingsService $settings): int
    {
        $locationId = (int) ($settings->rules()['wholesale_sales_location_id'] ?? 0);
        $location = $locationId ? StockLocation::query()->whereKey($locationId)->where('type', 'shop')->first() : null;
        if (! $location) {
            $this->error('La boutique grossiste n’est pas configurée (wholesale_sales_location_id). Aucune modification effectuée.');

            return self::FAILURE;
        }

        $remittances = FinanceCashRemittance::query()
            ->where('stock_location_id', $location->id)
            ->whereIn('status', ['received', 'discrepancy_open'])
            ->whereNotNull('received_at')
            ->orderBy('id')->get();
        $missing = $remittances->map(fn (FinanceCashRemittance $remittance): array => [
            'remittance' => $remittance,
            'missing_amount' => $this->missingHandoverAmount($remittance),
        ])->filter(fn (array $item): bool => $item['missing_amount'] > 0);

        $this->info('Boutique grossiste : '.$location->name.' (ID '.$location->id.')');
        $this->info('Remises confirmées vérifiées : '.$remittances->count());
        if ($missing->isEmpty()) {
            $this->info('Aucune sortie de caisse manquante détectée.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Référence', 'Réception DG', 'Débit manquant', 'Solde actuel boutique'], $missing->map(fn (array $item): array => [
            $item['remittance']->id,
            $item['remittance']->reference,
            $item['remittance']->received_at?->format('d/m/Y H:i'),
            number_format($item['missing_amount'], 0, ',', ' ').' F',
            number_format($ledger->balance('shop', (int) $location->id), 0, ',', ' ').' F',
        ])->all());

        if (! $this->option('apply')) {
            $this->warn('Mode vérification : aucune écriture faite. Relance avec --apply pour régulariser les remises dont le solde boutique permet le débit.');

            return self::SUCCESS;
        }

        $applied = 0;
        $skipped = 0;
        foreach ($missing as $item) {
            $remittance = $item['remittance'];
            $result = DB::transaction(function () use ($remittance, $location, $ledger): string {
                $locked = FinanceCashRemittance::query()->lockForUpdate()->findOrFail($remittance->id);
                if (! in_array($locked->status, ['received', 'discrepancy_open'], true) || ! $locked->received_at) {
                    return 'skipped';
                }
                $amount = $this->missingHandoverAmount($locked);
                if ($amount <= 0) {
                    return 'already_fixed';
                }

                if ($ledger->balance('shop', (int) $location->id) < $amount) {
                    return 'insufficient';
                }

                $ledger->record([
                    'movement_type' => 'remittance_handover',
                    'from_custody' => 'shop',
                    'to_custody' => 'transit',
                    'amount' => $amount,
                    'stock_location_id' => $location->id,
                    'remittance_id' => $locked->id,
                    'source_type' => 'cash_remittance',
                    'source_id' => $locked->id,
                    'source_key' => 'remittance:'.$locked->id.':handover-reconciled',
                    'source_reference' => $locked->reference,
                    'occurred_at' => $locked->collector_signed_at ?? $locked->received_at,
                    'description' => 'Sortie de caisse grossiste régularisée après vérification d’une remise reçue par le DG.',
                    'created_by' => $locked->received_by ?? $locked->created_by,
                ]);

                return 'applied';
            });

            if ($result === 'applied') {
                $applied++;
                $this->line('Régularisée : '.$remittance->reference);
            } elseif ($result === 'insufficient') {
                $skipped++;
                $this->warn('Non régularisée, solde boutique insuffisant : '.$remittance->reference.'. Vérification comptable nécessaire.');
            } elseif ($result === 'already_fixed') {
                $this->line('Déjà corrigée : '.$remittance->reference);
            } else {
                $skipped++;
                $this->warn('Ignorée car le statut a changé : '.$remittance->reference);
            }
        }

        $this->info($applied.' sortie(s) régularisée(s), '.$skipped.' remise(s) ignorée(s).');

        return self::SUCCESS;
    }

    private function missingHandoverAmount(FinanceCashRemittance $remittance): int
    {
        $handedOver = (int) FinanceCashMovement::query()
            ->where('remittance_id', $remittance->id)
            ->where('movement_type', 'remittance_handover')
            ->where('from_custody', 'shop')
            ->where('to_custody', 'transit')
            ->sum('amount');

        return max(0, (int) $remittance->handed_over_amount - $handedOver);
    }
}
