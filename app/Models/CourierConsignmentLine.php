<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierConsignmentLine extends Model
{
    protected $fillable = ['run_id', 'product_id', 'quantity_assigned', 'quantity_sold', 'quantity_returned', 'unit_price'];
    protected $casts = ['quantity_assigned' => 'decimal:3', 'quantity_sold' => 'decimal:3', 'quantity_returned' => 'decimal:3', 'unit_price' => 'integer'];
    public function run(): BelongsTo { return $this->belongsTo(CourierConsignmentRun::class, 'run_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
