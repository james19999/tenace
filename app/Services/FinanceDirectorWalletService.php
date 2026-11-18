<?php

namespace App\Services;

use App\Models\EmployeePayrollPeriod;
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
            "COALESCE(SUM(CASE WHEN entry_type IN ('opening','salary') THEN amount ELSE 0 END),0) as credits, COALESCE(SUM(CASE WHEN entry_type='withdrawal' THEN amount ELSE 0 END),0) as withdrawals"
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
            'balance' => (int) $totals->credits - (int) $totals->withdrawals,
            'percentage_total' => (int) $wallet->categories->sum('percentage'),
            'opening_exists' => $wallet->entries()->where('entry_type', 'opening')->exists(),
            'entries' => $wallet->entries()->with('category')->latest('occurred_at')->latest('id')->paginate(15),
        ];
    }

    public function syncAvailableRemuneration(FinanceDirectorWallet $wallet): int
    {
        $periods = EmployeePayrollPeriod::query()->where('user_id', $wallet->user_id)
            ->whereNotNull('available_at')->where('period_start', '>=', $wallet->started_at->copy()->startOfMonth())
            ->where('salary_amount', '>', 0)->orderBy('period_start')->get();
        $added = 0;
        foreach ($periods as $period) {
            $added += DB::transaction(function () use ($wallet, $period): int {
                $locked = FinanceDirectorWallet::query()->lockForUpdate()->findOrFail($wallet->id);
                $key = 'payroll-period:'.$period->id;
                if ($locked->entries()->where('source_key', 'like', $key.':%')->exists()) {
                    return 0;
                }
                $categories = $locked->categories()->lockForUpdate()->get();
                if ($categories->isEmpty() || (int) $categories->sum('percentage') !== 100) {
                    return 0;
                }
                $this->allocate($locked, $categories, (int) $period->salary_amount, 'salary', $key,
                    'Rémunération DG · '.$period->period_start->translatedFormat('F Y'), $period->available_at, $wallet->user_id);
                return (int) $period->salary_amount;
            });
        }

        return $added;
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
}
