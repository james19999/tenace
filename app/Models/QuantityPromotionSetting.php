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
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tiers' => 'array',
        'product_ids' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'duration_days' => 'integer',
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
            ->filter(fn ($tier) => (int) ($tier['min_quantity'] ?? 0) <= $quantity)
            ->sortByDesc(fn ($tier) => (int) $tier['min_quantity'])
            ->first(fn ($tier) => true)['discount_percent'] ?? 0;
    }

    public function appliesToProduct(int $productId): bool
    {
        return $this->isAvailableNow() && (
            $this->application_mode !== 'product'
            || in_array($productId, array_map('intval', $this->product_ids ?? []), true)
        );
    }

    public function isAvailableNow(): bool
    {
        if (! $this->is_active) return false;
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at && $now->gte($this->ends_at)) return false;
        return true;
    }

    public function nextStatusChangeTimestamp(): ?int
    {
        if (! $this->is_active) return null;
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) return $this->starts_at->timestamp;
        if ($this->ends_at && $now->lt($this->ends_at)) return $this->ends_at->timestamp;
        return null;
    }
}
