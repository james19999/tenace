<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerCourierStockIssueLine extends Model
{
    protected $fillable = ['issue_id', 'product_id', 'quantity_issued', 'quantity_remaining'];

    protected $casts = ['quantity_issued' => 'integer', 'quantity_remaining' => 'integer'];

    public function issue(): BelongsTo { return $this->belongsTo(PartnerCourierStockIssue::class, 'issue_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
