<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLoyaltyScoreHistory extends Model
{
    protected $fillable = ['costumer_id', 'score', 'category', 'metrics', 'snapshot_date'];
    protected $casts = ['metrics' => 'array', 'snapshot_date' => 'date'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Costumer::class, 'costumer_id');
    }
}
