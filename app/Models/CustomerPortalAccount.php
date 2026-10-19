<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerPortalAccount extends Model
{
    protected $fillable = ['phone_e164', 'password'];

    protected $hidden = ['password'];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Costumer::class, 'customer_portal_account_costumer')
            ->withTimestamps();
    }
}
