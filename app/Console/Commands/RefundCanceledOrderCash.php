<?php

namespace App\Console\Commands;

use App\Models\FinanceCashMovement;
use App\Models\Orders\Order;
use App\Models\User;
use App\Services\FinanceOrderCashIntegrationService;
use Illuminate\Console\Command;

class RefundCanceledOrderCash extends Command
{
    protected $signature = 'finance:refund-canceled-order-cash {--apply : Enregistrer les remboursements dans la caisse}';

    protected $description = 'Prévisualise ou régularise les espèces liées aux commandes annulées.';

    public function handle(FinanceOrderCashIntegrationService $cashIntegration): int
    {
        $orders = Order::query()->where('status', 'canceled')
            ->whereNull('partner_id')->whereNull('public_order_link_id')
            ->whereHas('payments', function ($query): void {
                $query->where('status', 'collected')->whereIn('method', ['cash', 'manual'])
                    ->whereHas('cashMovements', function ($movements): void {
                        $movements->where('movement_type', 'sale_cash_received')
                            ->where('source_type', 'order_payment');
                    });
            })->with(['payments' => function ($query): void {
                $query->where('status', 'collected')->whereIn('method', ['cash', 'manual'])
                    ->with(['cashMovements' => function ($movements): void {
                        $movements->where('movement_type', 'sale_cash_received')
                            ->where('source_type', 'order_payment');
                    }]);
            }])->get();

        $pending = $orders->map(function (Order $order): array {
            $movements = $order->payments->flatMap->cashMovements;
            $unrefunded = $movements->filter(fn (FinanceCashMovement $movement): bool =>
                ! FinanceCashMovement::query()->where('source_key', 'order-cancel-refund:'.$movement->id)->exists()
            );

            return [
                'order' => $order,
                'count' => $unrefunded->count(),
                'amount' => (int) $unrefunded->sum('amount'),
            ];
        })->filter(fn (array $row): bool => $row['count'] > 0)->values();

        $total = (int) $pending->sum('amount');
        $this->table(
            ['Commande', 'Paiements à régulariser', 'Montant'],
            $pending->map(fn (array $row): array => [
                $row['order']->code ?: $row['order']->id,
                $row['count'],
                number_format($row['amount'], 0, ',', ' ').' F',
            ])->all()
        );
        $this->info('Total à régulariser : '.number_format($total, 0, ',', ' ').' F.');

        if (! $this->option('apply')) {
            $this->comment('Aucun mouvement n’a été créé. Relance avec --apply après avoir confirmé que ces espèces ont bien été remboursées.');

            return self::SUCCESS;
        }

        $actor = User::query()->where('user_type', 'ADMINUSER')->first();
        if (! $actor) {
            $this->error('Aucun compte ADMINUSER trouvé pour tracer la régularisation.');

            return self::FAILURE;
        }

        foreach ($pending as $row) {
            $cashIntegration->refundCashForCanceledOrder($row['order'], $actor);
        }

        $this->info('Régularisation enregistrée avec des mouvements de remboursement historisés.');

        return self::SUCCESS;
    }
}
