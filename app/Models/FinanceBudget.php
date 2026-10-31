<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceBudget extends Model
{
    protected $fillable = ['category_name', 'stock_location_id', 'period_start', 'period_end', 'amount', 'note', 'created_by'];
    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'amount' => 'integer'];

    public function location(): BelongsTo { return $this->belongsTo(StockLocation::class, 'stock_location_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
