<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerServiceSupportMilestone extends Model
{
    protected $fillable = ['plan_id', 'day_offset', 'due_at', 'completed_at', 'observation', 'completed_by'];
    protected $casts = ['due_at' => 'datetime', 'completed_at' => 'datetime'];
    public function plan(): BelongsTo { return $this->belongsTo(CustomerServiceSupportPlan::class, 'plan_id'); }
    public function completedBy(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
}
