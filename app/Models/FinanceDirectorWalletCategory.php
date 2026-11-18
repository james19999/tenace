<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceDirectorWalletCategory extends Model
{
    protected $fillable = ['wallet_id', 'name', 'percentage'];

    protected $casts = ['percentage' => 'integer'];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(FinanceDirectorWallet::class, 'wallet_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FinanceDirectorWalletEntry::class, 'category_id');
    }
}
