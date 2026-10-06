<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerServiceSupportPlan extends Model
{
    protected $fillable = ['case_id', 'customer_need', 'objectives', 'started_at', 'final_review', 'completed_at'];
    protected $casts = ['started_at' => 'date', 'completed_at' => 'datetime'];
    public function customerServiceCase(): BelongsTo { return $this->belongsTo(CustomerServiceCase::class, 'case_id'); }
    public function milestones(): HasMany { return $this->hasMany(CustomerServiceSupportMilestone::class, 'plan_id')->orderBy('due_at'); }
}
