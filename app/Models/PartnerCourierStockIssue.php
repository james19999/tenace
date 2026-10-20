<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerCourierStockIssue extends Model
{
    protected $fillable = ['partner_id', 'courier_id', 'issued_by', 'country_code', 'issued_at', 'notes'];

    protected $casts = ['issued_at' => 'datetime'];

    public function partner(): BelongsTo { return $this->belongsTo(User::class, 'partner_id'); }
    public function courier(): BelongsTo { return $this->belongsTo(User::class, 'courier_id'); }
    public function lines(): HasMany { return $this->hasMany(PartnerCourierStockIssueLine::class, 'issue_id'); }
}
