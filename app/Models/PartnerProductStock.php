<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerProductStock extends Model
{
    protected $fillable = ['partner_id', 'product_id', 'country_code', 'quantity'];

    protected $casts = ['quantity' => 'integer'];

    public function partner(): BelongsTo { return $this->belongsTo(User::class, 'partner_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
