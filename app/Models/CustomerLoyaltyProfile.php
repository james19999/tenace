<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLoyaltyProfile extends Model
{
    protected $fillable = ['costumer_id', 'score', 'category', 'metrics', 'calculated_at'];
    protected $casts = ['metrics' => 'array', 'calculated_at' => 'datetime'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Costumer::class, 'costumer_id');
    }
}
