<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceAsset extends Model
{
    protected $fillable = ['reference', 'name', 'category', 'acquired_on', 'purchase_amount', 'residual_amount', 'useful_life_months', 'stock_location_id', 'supplier_name', 'document_path', 'status', 'notes', 'created_by'];
    protected $casts = ['acquired_on' => 'date', 'purchase_amount' => 'integer', 'residual_amount' => 'integer', 'useful_life_months' => 'integer'];

    public function location(): BelongsTo { return $this->belongsTo(StockLocation::class, 'stock_location_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
