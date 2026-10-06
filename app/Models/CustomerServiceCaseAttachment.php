<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerServiceCaseAttachment extends Model
{
    protected $fillable = ['case_id', 'activity_id', 'uploaded_by', 'path', 'original_name', 'mime_type', 'size'];
    public function customerServiceCase(): BelongsTo { return $this->belongsTo(CustomerServiceCase::class, 'case_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
