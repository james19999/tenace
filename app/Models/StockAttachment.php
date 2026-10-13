<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockAttachment extends Model
{
    protected $fillable = [
        'subject_type', 'subject_id', 'uploaded_by', 'path', 'original_name', 'mime_type', 'size',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
