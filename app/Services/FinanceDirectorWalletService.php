<?php

namespace App\Services;

use App\Models\FinanceCashCompartment;
use App\Models\FinanceDirectorWallet;
use App\Models\FinanceDirectorWalletCategory;
use App\Models\FinanceDirectorWalletEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceDirectorWalletService
{
    public function openFor(int $userId): FinanceDirectorWallet
    {
        return FinanceDirectorWallet::query()->firstOrCreate(
            ['user_id' => $userId],
            ['started_at' => now()],
        );
    }

    public function summary(FinanceDirectorWallet $wallet): array
    {
        $wallet->load('categories');
        $totals = $wallet->entries()->selectRaw(
            "COALESCE(SUM(CASE WHEN entry_type IN ('opening','salary') THEN amount ELSE 0 END),0) as credits, COALESCE(SUM(CASE WHEN entry_type='withdrawal' THEN amount ELSE 0 END),0) as withdrawals, COALESCE(SUM(CASE WHEN entry_type='external_decrease' THEN amount ELSE 0 END),0) as external_decreases"
        )->first();
        $wallet->categories->each(function (FinanceDirectorWalletCategory $category) use ($wallet): void {
            $category->setAttribute('balance', (int) $wallet->entries()->where('category_id', $category->id)
                ->selectRaw("COALESCE(SUM(CASE WHEN entry_type IN ('opening','salary') THEN amount ELSE -amount END),0) as balance")
                ->value('balance'));
        });

        return [
            'wallet' => $wallet,
            'categories' => $wallet->categories,
            'credits' => (int) $totals->credits,
            'withdrawals' => (int) $totals->withdrawals,
            'external_decreases' => (int) $totals->external_decreases,
            'balance' => (int) $totals->credits - (int) $totals->withdrawals - (int) $totals->external_decreases,
            'remuneration_compartment_imported_balance' => (int) $wallet->remuneration_compartment_synced_balance,
            'percentage_total' => (int) $wallet->categories->sum('percentage'),
            'opening_exists' => $wallet->entries()->where('entry_type', 'opening')->exists(),
            'entries' => $wallet->entries()->with('category')->latest('occurred_at')->latest('id')->paginate(15),
        ];
    }

    public function syncRemunerationCompartment(FinanceDirectorWallet $wallet): int
    {
        $compartment = FinanceCashCompartment::query()->where('key', 'dg_remuneration')->first();
        $actualBalance = $compartment
            ? (int) $compartment->entries()->selectRaw(
                "COALESCE(SUM(CASE WHEN entry_type='allocation' THEN amount ELSE -amount END),0) as balance"
            )->value('balance')
            : 0;

        return DB::transaction(function () use ($wallet, $actualBalance): int {
            $locked = FinanceDirectorWallet::query()->lockForUpdate()->findOrFail($wallet->id);
            $previous = (int) $locked->remuneration_compartment_synced_balance;
            $delta = $actualBalance - $previous;
            if ($delta === 0) {
                return $actualBalance;
            }
            $categories = $locked->categories()->lockForUpdate()->get();
            if ($categories->isEmpty() || (int) $categories->sum('percentage') !== 100) {
                return $actualBalance;
            }

            if ($delta > 0) {
                $this->allocate($locked, $categories, $delta, 'salary', 'dg-compartment-sync:'.\Illuminate\Support\Str::uuid(),
                    'Solde synchronisé depuis Répartition des fonds · Rémunération du DG', now(), $wallet->user_id);
            } else {
                $this->deductCompartmentDecrease($locked, $categories, abs($delta), $wallet->user_id);
            }
            $locked->update(['remuneration_compartment_synced_balance' => max(0, $actualBalance)]);

            return $actualBalance;
        });
    }

    public function recordCredit(FinanceDirectorWallet $wallet, int $amount, string $type, string $sourceKey, string $description, int $actorId): void
    {
        DB::transaction(function () use ($wallet, $amount, $type, $sourceKey, $description, $actorId): void {
            $locked = FinanceDirectorWallet::query()->lockForUpdate()->findOrFail($wallet->id);
            if ($locked->entries()->where('source_key', 'like', $sourceKey.':%')->exists()) {
                throw ValidationException::withMessages(['amount' => 'Ce montant de départ a déjà été enregistré.']);
            }
            $categories = $locked->categories()->lockForUpdate()->get();
            if ($categories->isEmpty() || (int) $categories->sum('percentage') !== 100) {
                throw ValidationException::withMessages(['percentages' => 'Configure des catégories dont les pourcentages totalisent 100 % avant d’enregistrer un montant.']);
            }
            $this->allocate($locked, $categories, $amount, $type, $sourceKey, $description, now(), $actorId);
        });
    }

    public function withdraw(FinanceDirectorWallet $wallet, int $categoryId, int $amount, string $description, int $actorId): void
    {
        DB::transaction(function () use ($wallet, $categoryId, $amount, $description, $actorId): void {
            $locked = FinanceDirectorWallet::query()->lockForUpdate()->findOrFail($wallet->id);
            $category = $locked->categories()->lockForUpdate()->findOrFail($categoryId);
            $balance = (int) $locked->entries()->where('category_id', $category->id)->selectRaw(
                "COALESCE(SUM(CASE WHEN entry_type IN ('opening','salary') THEN amount ELSE -amount END),0) as balance"
            )->value('balance');
            if ($amount > $balance) {
                throw ValidationException::withMessages(['amount' => 'Le montant dépasse le solde disponible dans cette catégorie ('.number_format($balance, 0, ',', ' ').' F CFA).']);
            }
            $locked->entries()->create([
                'category_id' => $category->id, 'entry_type' => 'withdrawal', 'amount' => $amount,
                'source_key' => 'withdrawal:'.\Illuminate\Support\Str::uuid(), 'description' => $description,
                'occurred_at' => now(), 'created_by' => $actorId,
            ]);
        });
    }

    private function allocate(FinanceDirectorWallet $wallet, $categories, int $amount, string $type, string $sourceKey, string $description, $occurredAt, int $actorId): void
    {
        $parts = [];
        $remainders = [];
        $remaining = $amount;
        foreach ($categories as $category) {
            $weighted = $amount * (int) $category->percentage;
            $part = intdiv($weighted, 100);
            $parts[] = [$category, $part];
            if ($category->percentage > 0) {
                $remainders[] = ['index' => count($parts) - 1, 'fraction' => $weighted % 100, 'id' => $category->id];
            }
            $remaining -= $part;
        }
        usort($remainders, fn ($a, $b) => [$b['fraction'], $a['id']] <=> [$a['fraction'], $b['id']]);
        for ($i = 0; $i < $remaining; $i++) {
            $parts[$remainders[$i]['index']][1]++;
        }
        foreach ($parts as [$category, $part]) {
            if ($part === 0) {
                continue;
            }
            $wallet->entries()->create([
                'category_id' => $category->id, 'entry_type' => $type, 'amount' => $part,
                'source_key' => $sourceKey.':'.$category->id, 'description' => $description,
                'occurred_at' => $occurredAt, 'created_by' => $actorId,
            ]);
        }
    }

    private function deductCompartmentDecrease(FinanceDirectorWallet $wallet, $categories, int $amount, int $actorId): void
    {
        $balances = [];
        $total = 0;
        foreach ($categories as $category) {
            $balance = (int) $wallet->entries()->where('category_id', $category->id)->selectRaw(
                "COALESCE(SUM(CASE WHEN entry_type IN ('opening','salary') THEN amount ELSE -amount END),0) as balance"
            )->value('balance');
            $balances[] = [$category, max(0, $balance)];
            $total += max(0, $balance);
        }
        $deduction = $amount;
        $weightTotal = $total > 0 ? $total : (int) $categories->sum('percentage');
        if ($weightTotal <= 0) {
            return;
        }
        $remaining = $deduction;
        $parts = [];
        $remainders = [];
        foreach ($balances as [$category, $balance]) {
            $weight = $total > 0 ? $balance : (int) $category->percentage;
            $weighted = $deduction * $weight;
            $part = intdiv($weighted, $weightTotal);
            $parts[] = [$category, $part];
            if ($weight > 0) {
                $remainders[] = ['index' => count($parts) - 1, 'fraction' => $weighted % $weightTotal, 'id' => $category->id];
            }
            $remaining -= $part;
        }
        usort($remainders, fn ($a, $b) => [$b['fraction'], $a['id']] <=> [$a['fraction'], $b['id']]);
        for ($index = 0; $index < $remaining; $index++) {
            $parts[$remainders[$index]['index']][1]++;
        }

        foreach ($parts as [$category, $part]) {
            if ($part <= 0) {
                continue;
            }
            $wallet->entries()->create([
                'category_id' => $category->id, 'entry_type' => 'external_decrease', 'amount' => $part,
                'source_key' => 'dg-compartment-decrease:'.\Illuminate\Support\Str::uuid().':'.$category->id,
                'description' => 'Baisse synchronisée du solde Rémunération du DG', 'occurred_at' => now(), 'created_by' => $actorId,
            ]);
        }
    }
}
