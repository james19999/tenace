<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLoyaltyReferral extends Model
{
    protected $fillable = ['referrer_costumer_id', 'referred_costumer_id', 'recorded_by', 'referred_at', 'note'];
    protected $casts = ['referred_at' => 'date'];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Costumer::class, 'referrer_costumer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(Costumer::class, 'referred_costumer_id');
    }
}
