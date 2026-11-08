<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierConsignmentEvent extends Model
{
    protected $fillable = ['run_id', 'event_type', 'user_id', 'stock_movement_id', 'cash_movement_id', 'amount', 'lines', 'notes', 'occurred_at'];
    protected $casts = ['lines' => 'array', 'amount' => 'integer', 'occurred_at' => 'datetime'];
    public function run(): BelongsTo { return $this->belongsTo(CourierConsignmentRun::class, 'run_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
