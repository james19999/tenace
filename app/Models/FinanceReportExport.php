<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceReportExport extends Model
{
    public $timestamps = false;

    protected $fillable = ['reference', 'report_type', 'filters', 'row_count', 'amount_total', 'exported_by', 'exported_at'];

    protected $casts = [
        'filters' => 'array',
        'row_count' => 'integer',
        'amount_total' => 'integer',
        'exported_at' => 'datetime',
    ];

    public function exporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by');
    }
}
