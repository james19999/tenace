<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceCashCompartment extends Model
{
    protected $fillable = ['key', 'name', 'description', 'percentage'];

    protected $casts = ['percentage' => 'integer'];

    public function entries(): HasMany
    {
        return $this->hasMany(FinanceCashCompartmentEntry::class, 'compartment_id');
    }
}
