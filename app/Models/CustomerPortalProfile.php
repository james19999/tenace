<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPortalProfile extends Model
{
    protected $fillable = [
        'customer_portal_account_id',
        'source_costumer_id',
        'name',
        'phone',
        'email',
        'address',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(CustomerPortalAccount::class, 'customer_portal_account_id');
    }
}
