<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPortalAccount extends Model
{
    protected $fillable = ['phone_e164', 'password'];

    protected $hidden = ['password'];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Costumer::class, 'customer_portal_account_costumer')
            ->withTimestamps();
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(CustomerPortalProfile::class, 'customer_portal_account_id');
    }
}
