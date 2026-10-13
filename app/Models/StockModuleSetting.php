<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockModuleSetting extends Model
{
    protected $fillable = ['rules', 'updated_by'];

    protected $casts = ['rules' => 'array'];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
