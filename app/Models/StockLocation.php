<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLocation extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'responsible_user_id', 'parent_location_id',
        'is_active', 'valid_from', 'valid_until', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_location_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_location_id');
    }

    public function movementLines(): HasMany
    {
        return $this->hasMany(StockMovementLine::class, 'location_id');
    }
}
