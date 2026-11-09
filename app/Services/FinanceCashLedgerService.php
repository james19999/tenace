<?php

namespace App\Services;

use App\Models\FinanceCashMovement;
use App\Models\FinanceCashClosure;
use App\Models\Orders\Order;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinanceCashLedgerService
{
    private const CUSTODIES = ['external', 'shop', 'transit', 'general_cash'];

    public function record(array $attributes): FinanceCashMovement
    {
        return DB::transaction(function () use ($attributes): FinanceCashMovement {
            $compartmentKey = $attributes['compartment_key'] ?? null;
            unset($attributes['compartment_key']);
            $sourceKey = $attributes['source_key'] ?? null;
            if ($sourceKey) {
                $existing = FinanceCashMovement::query()->where('source_key', $sourceKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            $from = $attributes['from_custody'];
            $to = $attributes['to_custody'];
            $amount = (int) $attributes['amount'];
            if (! in_array($from, self::CUSTODIES, true) || ! in_array($to, self::CUSTODIES, true) || $from === $to || $amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Le mouvement de caisse est invalide.']);
            }

            if (in_array('general_cash', [$from, $to], true)) {
                $movementAt = \Illuminate\Support\Carbon::parse($attributes['occurred_at']);
                $movementDate = $movementAt->toDateString();
                $awaitingReview = FinanceCashClosure::query()->whereDate('closing_date', $movementDate)
                    ->where('status', 'awaiting_review')->exists();
                $closedClosure = FinanceCashClosure::query()->whereDate('closing_date', $movementDate)
                    ->where('status', 'closed')->orderByDesc('reviewed_at')->first();
                $isApprovedClosureAdjustment = ($attributes['source_type'] ?? null) === 'finance_cash_closure'
                    && ! empty($attributes['source_id'])
                    && FinanceCashClosure::query()
                        ->whereKey($attributes['source_id'])
                        ->where('status', 'closed')
                        ->where('reviewed_by', $attributes['created_by'] ?? auth()->id())
                        ->exists();
                $beforeValidatedCount = $closedClosure
                    && ($closedClosure->reviewed_at === null || ! $movementAt->greaterThan($closedClosure->reviewed_at));
                if (($awaitingReview || $beforeValidatedCount) && ! $isApprovedClosureAdjustment) {
                    throw ValidationException::withMessages(['amount' => 'Ce mouvement est antérieur au dernier comptage validé ou à son contrôle. La caisse reste ouverte pour les nouvelles opérations après validation.']);
                }
            }

            $lockKeys = array_values(array_unique(array_filter([
                $this->lockKey($from, $attributes),
                $this->lockKey($to, $attributes),
            ])));
            sort($lockKeys);
            foreach ($lockKeys as $key) {
                DB::table('finance_cash_locks')->insertOrIgnore([
                    'lock_key' => $key,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('finance_cash_locks')->whereIn('lock_key', $lockKeys)->orderBy('lock_key')->lockForUpdate()->get();

            if ($from !== 'external') {
                $balance = $this->balance($from, $attributes['stock_location_id'] ?? null, $attributes['remittance_id'] ?? null);
                if ($balance < $amount) {
                    throw ValidationException::withMessages([
                        'amount' => 'Le solde disponible dans cette caisse est insuffisant. Solde actuel : '.number_format($balance, 0, ',', ' ').' F.',
                    ]);
                }
            }

            $movement = FinanceCashMovement::query()->create(array_merge($attributes, [
                'entry_number' => 'TMP-'.Str::uuid(),
                'created_by' => $attributes['created_by'] ?? auth()->id(),
            ]));
            $movement->update(['entry_number' => sprintf('FIN-%s-%08d', now()->format('Y'), $movement->id)]);

            app(FinanceCashCompartmentsService::class)->recordMovement($movement->fresh(), $compartmentKey);

            return $movement->fresh();
        });
    }

    public function balance(string $custody, ?int $locationId = null, ?int $remittanceId = null): int
    {
        $query = FinanceCashMovement::query();
        if ($custody === 'shop') {
            $query->where('stock_location_id', $locationId);
        } elseif ($custody === 'transit') {
            $query->where('remittance_id', $remittanceId);
        }

        return (int) $query->selectRaw(
            'COALESCE(SUM(CASE WHEN to_custody = ? THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN from_custody = ? THEN amount ELSE 0 END), 0) as balance',
            [$custody, $custody]
        )->value('balance');
    }

    public function initializeGeneralCash(int $amount, string $occurredAt, User $director, string $reason): FinanceCashMovement
    {
        abort_unless($director->isAdmin(), 403);
        if ($amount < 0) {
            throw ValidationException::withMessages(['amount' => 'Le fonds initial ne peut pas être négatif.']);
        }

        return DB::transaction(function () use ($amount, $occurredAt, $director, $reason): FinanceCashMovement {
            if (DB::table('finance_general_openings')->exists()) {
                throw ValidationException::withMessages(['amount' => 'Le fonds initial de la caisse générale a déjà été enregistré. Toute correction doit passer par un mouvement tracé.']);
            }
            DB::table('finance_general_openings')->insert([
                'opening_key' => 'general_cash', 'amount' => $amount, 'occurred_at' => $occurredAt, 'reason' => $reason,
                'created_by' => $director->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($amount === 0) {
                return new FinanceCashMovement(['amount' => 0, 'description' => $reason, 'created_by' => $director->id]);
            }

            return $this->record([
                'movement_type' => 'opening_balance',
                'from_custody' => 'external',
                'to_custody' => 'general_cash',
                'amount' => $amount,
                'source_type' => 'opening_balance',
                'source_key' => 'opening:general_cash',
                'source_reference' => 'Fonds initial caisse générale',
                'occurred_at' => $occurredAt,
                'description' => $reason,
                'created_by' => $director->id,
            ]);
        });
    }

    public function initializeShopCash(int $locationId, int $amount, string $occurredAt, User $actor, string $reason): void
    {
        abort_unless($actor->isAdmin() || $actor->isManager(), 403);
        if ($amount < 0) {
            throw ValidationException::withMessages(['amount' => 'Le fonds initial ne peut pas être négatif.']);
        }

        DB::transaction(function () use ($locationId, $amount, $occurredAt, $actor, $reason): void {
            $location = StockLocation::query()->whereKey($locationId)->where('type', 'shop')->where('is_active', true)->firstOrFail();
            if (! $actor->isAdmin() && (int) $location->responsible_user_id !== (int) $actor->id) {
                abort(403);
            }
            $exists = DB::table('finance_shop_openings')->where('stock_location_id', $location->id)->exists();
            if ($exists) {
                throw ValidationException::withMessages(['stock_location_id' => 'Le fonds initial de cette boutique est déjà enregistré. Toute correction doit être validée et historisée.']);
            }
            DB::table('finance_shop_openings')->insert([
                'stock_location_id' => $location->id, 'amount' => $amount, 'occurred_at' => $occurredAt,
                'reason' => $reason, 'created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($amount > 0) {
                $this->record([
                    'movement_type' => 'opening_balance', 'from_custody' => 'external', 'to_custody' => 'shop',
                    'amount' => $amount, 'stock_location_id' => $location->id,
                    'source_type' => 'opening_balance', 'source_key' => 'opening:shop:'.$location->id,
                    'source_reference' => 'Fonds initial boutique '.$location->name, 'occurred_at' => $occurredAt,
                    'description' => $reason, 'created_by' => $actor->id,
                ]);
            }
        });
    }

    public function expectedShopCash(string $from, string $to): int
    {
        // Remittance expectations follow the DG dashboard's weekly turnover:
        // delivered boutique orders, merchandise amount only, excluding wholesale.
        return (int) Order::query()->where('status', 'delivered')
            ->where('is_wholesale', false)
            ->whereBetween('delivered_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->sum('total');
    }

    /**
     * Explain the physical shop-cash balance at period end. The dashboard's
     * weekly turnover is gross delivered sales across all locations; this is
     * the cash remaining in one shop after its recorded ins and outs.
     */
    public function shopCashSummary(int $locationId, string $from, string $to): array
    {
        $base = FinanceCashMovement::query()->where('stock_location_id', $locationId);
        $openingBalance = (int) (clone $base)
            ->where('occurred_at', '<', $from.' 00:00:00')
            ->selectRaw("COALESCE(SUM(CASE WHEN to_custody = 'shop' THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN from_custody = 'shop' THEN amount ELSE 0 END), 0) as balance")
            ->value('balance');
        $periodMovements = (clone $base)->where('occurred_at', '>=', $from.' 00:00:00')
            ->where('occurred_at', '<=', $to.' 23:59:59');
        $inflows = (int) (clone $periodMovements)->where('to_custody', 'shop')->sum('amount');
        $outflows = (int) (clone $periodMovements)->where('from_custody', 'shop')->sum('amount');
        $inflowDetails = (clone $periodMovements)->where('to_custody', 'shop')
            ->select('movement_type')->selectRaw('SUM(amount) as amount')->groupBy('movement_type')->get()
            ->map(fn ($row): array => ['movement_type' => $row->movement_type, 'amount' => (int) $row->amount])->values()->all();
        $outflowDetails = (clone $periodMovements)->where('from_custody', 'shop')
            ->select('movement_type')->selectRaw('SUM(amount) as amount')->groupBy('movement_type')->get()
            ->map(fn ($row): array => ['movement_type' => $row->movement_type, 'amount' => (int) $row->amount])->values()->all();

        return [
            'opening_balance' => $openingBalance,
            'period_inflows' => $inflows,
            'period_outflows' => $outflows,
            'closing_balance' => max(0, $openingBalance + $inflows - $outflows),
            'inflow_details' => $inflowDetails,
            'outflow_details' => $outflowDetails,
        ];
    }

    private function lockKey(string $custody, array $attributes): ?string
    {
        return match ($custody) {
            'general_cash' => 'general_cash',
            'shop' => isset($attributes['stock_location_id']) ? 'shop:'.$attributes['stock_location_id'] : null,
            'transit' => isset($attributes['remittance_id']) ? 'transit:'.$attributes['remittance_id'] : null,
            default => null,
        };
    }
}
