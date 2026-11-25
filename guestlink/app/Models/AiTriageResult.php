<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTriageResult extends Model
{
    protected $fillable = [
        'service_request_id', 'suggested_category', 'suggested_priority',
        'suggested_department', 'reason', 'status', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function setSuggestedPriorityAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['suggested_priority'] = 'medium';
            return;
        }
        $normalized = strtolower(trim((string) $value));
        $this->attributes['suggested_priority'] = in_array($normalized, ['low', 'medium', 'high', 'urgent'], true)
            ? $normalized
            : 'medium';
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
