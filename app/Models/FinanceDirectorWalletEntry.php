<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceDirectorWalletEntry extends Model
{
    protected $fillable = [
        'wallet_id', 'category_id', 'entry_type', 'amount', 'source_key', 'description', 'occurred_at', 'created_by',
    ];

    protected $casts = ['amount' => 'integer', 'occurred_at' => 'datetime'];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(FinanceDirectorWallet::class, 'wallet_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceDirectorWalletCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
