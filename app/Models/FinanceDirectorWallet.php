<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceDirectorWallet extends Model
{
    protected $fillable = ['user_id', 'started_at'];

    protected $casts = ['started_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(FinanceDirectorWalletCategory::class, 'wallet_id')->orderBy('id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FinanceDirectorWalletEntry::class, 'wallet_id');
    }
}
