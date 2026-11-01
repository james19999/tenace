<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceTontine extends Model
{
    protected $fillable = [
        'reference', 'name', 'first_contribution_on',
        'next_contribution_on', 'expected_recovery_on', 'cash_custody', 'stock_location_id', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'first_contribution_on' => 'date',
        'next_contribution_on' => 'date',
        'expected_recovery_on' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'finance_tontine_assignees', 'tontine_id', 'user_id')
            ->withPivot('assigned_by')->withTimestamps();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(FinanceTontineMovement::class, 'tontine_id')->orderBy('occurred_at')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }
}
