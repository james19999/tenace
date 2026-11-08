<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourierConsignmentRun extends Model
{
    protected $fillable = ['reference', 'run_date', 'shop_location_id', 'courier_location_id', 'courier_user_id', 'status', 'cash_received', 'assigned_by', 'closed_by', 'closed_at', 'notes'];
    protected $casts = ['run_date' => 'date', 'closed_at' => 'datetime', 'cash_received' => 'integer'];
    public function shop(): BelongsTo { return $this->belongsTo(StockLocation::class, 'shop_location_id'); }
    public function courier(): BelongsTo { return $this->belongsTo(User::class, 'courier_user_id'); }
    public function courierLocation(): BelongsTo { return $this->belongsTo(StockLocation::class, 'courier_location_id'); }
    public function lines(): HasMany { return $this->hasMany(CourierConsignmentLine::class, 'run_id'); }
    public function events(): HasMany { return $this->hasMany(CourierConsignmentEvent::class, 'run_id')->latest('occurred_at'); }
}
