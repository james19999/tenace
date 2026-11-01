<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceEquipmentPurchaseSetting extends Model
{
    protected $fillable = ['assignee_user_id', 'updated_by'];

    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assignee_user_id'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
