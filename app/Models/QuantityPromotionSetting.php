<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuantityPromotionSetting extends Model
{
    protected $fillable = [
        'is_active',
        'application_mode',
        'product_ids',
        'tiers',
        'starts_at',
        'ends_at',
        'duration_days',
        'periods',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tiers' => 'array',
        'product_ids' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'duration_days' => 'integer',
        'periods' => 'array',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], [
            'is_active' => false,
            'application_mode' => 'general',
            'product_ids' => [],
            'tiers' => [],
        ]);
    }

    public function discountForQuantity(int $quantity): int
    {
        if (! $this->isAvailableNow() || $quantity < 1) {
            return 0;
        }

        return collect($this->tiers ?? [])
            ->filter(fn ($tier) => empty($tier['product_id']) && (int) ($tier['min_quantity'] ?? 0) <= $quantity)
            ->sortByDesc(fn ($tier) => (int) $tier['min_quantity'])
            ->first(fn ($tier) => true)['discount_percent'] ?? 0;
    }

    public function discountForProductQuantity(int $productId, int $quantity): int
    {
        if (! $this->appliesToProduct($productId) || $quantity < 1) return 0;
        $tiers = collect($this->tiers ?? []);
        $productTiers = $tiers->filter(fn ($tier) => (int) ($tier['product_id'] ?? 0) === $productId);
        $eligibleTiers = $productTiers->isNotEmpty()
            ? $productTiers
            : $tiers->filter(fn ($tier) => empty($tier['product_id']));

        return $eligibleTiers->filter(fn ($tier) => (int) ($tier['min_quantity'] ?? 0) <= $quantity)
            ->sortByDesc(fn ($tier) => (int) ($tier['min_quantity'] ?? 0))
            ->first(fn ($tier) => true)['discount_percent'] ?? 0;
    }

    public function appliesToProduct(int $productId): bool
    {
        return $this->isAvailableNow() && (
            $this->application_mode !== 'product'
            || in_array($productId, array_map('intval', $this->product_ids ?? []), true)
        );
    }

    public function hasPromotionForProduct(int $productId): bool
    {
        if (! $this->appliesToProduct($productId)) return false;

        return collect($this->tiers ?? [])->contains(fn ($tier) =>
            (int) ($tier['product_id'] ?? 0) === $productId || empty($tier['product_id'])
        );
    }

    public function isAvailableNow(): bool
    {
        if (! $this->is_active) return false;
        $now = now();
        $periods = $this->schedulePeriods();
        if ($periods !== []) {
            foreach ($periods as $period) {
                $start = \Illuminate\Support\Carbon::parse($period['starts_at']);
                $end = \Illuminate\Support\Carbon::parse($period['ends_at']);
                if ($now->gte($start) && $now->lt($end)) return true;
            }
            return false;
        }
        // An explicitly saved empty schedule means the dates were removed.
        // Use legacy date columns only for rows created before schedules existed.
        if ($this->periods !== null) return true;
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at && $now->gte($this->ends_at)) return false;
        return true;
    }

    public function nextStatusChangeTimestamp(): ?int
    {
        if (! $this->is_active) return null;
        $now = now();
        $transitions = [];
        foreach ($this->schedulePeriods() as $period) {
            $start = \Illuminate\Support\Carbon::parse($period['starts_at']);
            $end = \Illuminate\Support\Carbon::parse($period['ends_at']);
            if ($start->gt($now)) $transitions[] = $start->timestamp;
            if ($end->gt($now)) $transitions[] = $end->timestamp;
        }
        if ($transitions !== []) return min($transitions);
        if ($this->periods !== null) return null;
        if ($this->starts_at && $now->lt($this->starts_at)) return $this->starts_at->timestamp;
        if ($this->ends_at && $now->lt($this->ends_at)) return $this->ends_at->timestamp;
        return null;
    }

    public function schedulePeriods(): array
    {
        $periods = collect($this->periods ?? [])->filter(fn ($period) => ! empty($period['starts_at']) && ! empty($period['duration_days']))
            ->map(function ($period): array {
                $start = \Illuminate\Support\Carbon::parse($period['starts_at']);
                $days = (int) $period['duration_days'];
                return [
                    'starts_at' => $start->toDateTimeString(),
                    'ends_at' => $start->copy()->addDays($days)->toDateTimeString(),
                    'duration_days' => $days,
                ];
            })->values()->all();
        if ($this->periods === null && $this->starts_at && $this->duration_days) {
            return [[
                'starts_at' => $this->starts_at->toDateTimeString(),
                'ends_at' => ($this->ends_at ?: $this->starts_at->copy()->addDays($this->duration_days))->toDateTimeString(),
                'duration_days' => (int) $this->duration_days,
            ]];
        }
        return $periods;
    }

    public function currentOrNextPeriod(): ?array
    {
        $now = now();
        $periods = collect($this->schedulePeriods())->map(function ($period) {
            $period['start_timestamp'] = \Illuminate\Support\Carbon::parse($period['starts_at'])->timestamp;
            $period['end_timestamp'] = \Illuminate\Support\Carbon::parse($period['ends_at'])->timestamp;
            return $period;
        });
        return $periods->first(function ($period) use ($now) {
            $start = \Illuminate\Support\Carbon::parse($period['starts_at']);
            $end = \Illuminate\Support\Carbon::parse($period['ends_at']);
            return $now->gte($start) && $now->lt($end);
        }) ?? $periods->filter(fn ($period) => \Illuminate\Support\Carbon::parse($period['starts_at'])->gt($now))
            ->sortBy('start_timestamp')->first();
    }
}
