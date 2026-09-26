<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostumerContactPreference extends Model
{
    protected $fillable = [
        'costumer_id',
        'calling_code',
        'do_not_contact_at',
        'do_not_contact_reason',
        'updated_by',
    ];

    protected $casts = ['do_not_contact_at' => 'datetime'];

    public function costumer(): BelongsTo
    {
        return $this->belongsTo(Costumer::class);
    }
}
