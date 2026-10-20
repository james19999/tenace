<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicOrderLink extends Model
{
    protected $fillable = ['token', 'title', 'country_code', 'is_active', 'expires_at', 'created_by'];

    protected $casts = ['is_active' => 'boolean', 'expires_at' => 'datetime'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'public_order_link_products')
            ->withPivot('sort_order')->withTimestamps()->orderByPivot('sort_order');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Orders\Order::class);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
