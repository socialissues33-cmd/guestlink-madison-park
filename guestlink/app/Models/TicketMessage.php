<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    protected $fillable = [
        'service_request_id',
        'sender_type',
        'sender_name',
        'user_id',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    protected $touches = [
        'serviceRequest',
    ];

    protected $appends = [
        'time_ago',
        'formatted_time',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at ? $this->created_at->diffForHumans(null, true) . ' ago' : 'Just now';
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('g:i A') : '';
    }
}
