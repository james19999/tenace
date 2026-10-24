<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuantityPromotionSetting extends Model
{
    protected $fillable = ['is_active', 'application_mode', 'product_ids', 'tiers'];

    protected $casts = [
        'is_active' => 'boolean',
        'tiers' => 'array',
        'product_ids' => 'array',
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
        if (! $this->is_active || $quantity < 1) {
            return 0;
        }

        return collect($this->tiers ?? [])
            ->filter(fn ($tier) => (int) ($tier['min_quantity'] ?? 0) <= $quantity)
            ->sortByDesc(fn ($tier) => (int) $tier['min_quantity'])
            ->first(fn ($tier) => true)['discount_percent'] ?? 0;
    }

    public function appliesToProduct(int $productId): bool
    {
        return $this->is_active && (
            $this->application_mode !== 'product'
            || in_array($productId, array_map('intval', $this->product_ids ?? []), true)
        );
    }
}
