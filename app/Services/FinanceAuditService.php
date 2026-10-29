<?php

namespace App\Services;

use App\Models\FinanceAuditEvent;
use Illuminate\Database\Eloquent\Model;

class FinanceAuditService
{
    public function record(Model $subject, string $action, ?array $before = null, ?array $after = null, ?string $reason = null): void
    {
        FinanceAuditEvent::query()->create([
            'subject_type' => class_basename($subject),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'before_values' => $before,
            'after_values' => $after,
            'reason' => $reason,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
    }
}
