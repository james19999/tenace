<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class CustomerServiceCaseActivity extends Model
{
    protected $fillable = ['case_id', 'user_id', 'actor_name', 'activity_type', 'channel', 'old_status', 'new_status', 'body', 'internal', 'occurred_at'];
    protected $casts = ['internal' => 'boolean', 'occurred_at' => 'datetime'];
    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            $activity->actor_name = $activity->actor_name ?: Auth::user()?->name;
        });
    }
    public function customerServiceCase(): BelongsTo { return $this->belongsTo(CustomerServiceCase::class, 'case_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function attachments(): HasMany { return $this->hasMany(CustomerServiceCaseAttachment::class, 'activity_id'); }
}
