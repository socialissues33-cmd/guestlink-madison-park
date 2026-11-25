<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class ServiceRequest extends Model
{
    protected $fillable = [
        'room_id', 'category_id', 'type', 'guest_name', 'description', 'photo_path',
        'status', 'priority', 'assigned_to', 'department', 'reference_number', 'auto_reply_bot',
        'guest_rating', 'guest_feedback',
    ];

    protected $casts = [
        'auto_reply_bot' => 'boolean',
        'guest_rating'   => 'integer',
    ];

    /**
     * Enforce canonical lowercase priority ('low', 'medium', 'high', 'urgent') per AGENTS.md.
     */
    public function setPriorityAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['priority'] = null;
            return;
        }
        $this->attributes['priority'] = strtolower(trim((string) $value));
    }

    /**
     * Check whether an urgent/high pending request has exceeded SLA (15 mins).
     */
    public function getIsSlaOverdueAttribute(): bool
    {
        if ($this->status !== 'pending' || !$this->created_at) {
            return false;
        }
        $p = strtolower((string) $this->priority);
        if (!in_array($p, ['urgent', 'high'], true)) {
            return false;
        }
        $limitMinutes = $p === 'urgent' ? 10 : 20;
        return $this->created_at->diffInMinutes(now()) >= $limitMinutes;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->reference_number)) {
                $model->reference_number = 'REQ-' . strtoupper(Str::random(8));
            }
        });
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RequestCategory::class, 'category_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function aiTriage(): HasOne
    {
        return $this->hasOne(AiTriageResult::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(RequestStatusLog::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('id', 'asc');
    }

    public function unreadGuestMessagesCount(): int
    {
        return $this->messages()->where('sender_type', 'guest')->where('is_read', false)->count();
    }

    /**
     * Determine whether this ticket is an Event / Function Hall inquiry & tentative hold
     * (handled via Front Office consultation & appointment rather than room-service workflow).
     */
    public function isEventInquiry(): bool
    {
        $catName = strtolower((string) ($this->category?->name ?? ''));
        $desc    = strtolower((string) ($this->description ?? ''));

        return str_contains($catName, 'function hall')
            || str_contains($catName, 'event')
            || str_contains($desc, 'function hall reservation inquiry')
            || str_contains($desc, 'function hall booking inquiry');
    }

    /**
     * Context-aware status label (Event Inquiries use Tentative Hold / FO Appointment stages).
     */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->isEventInquiry()) {
            return match($this->status) {
                'pending'     => 'Inquiry Received',
                'assigned'    => 'Tentative Hold',
                'in_progress' => 'FO Appointment',
                'resolved'    => 'Booking Confirmed',
                'closed'      => 'Closed',
                default       => ucwords(str_replace('_', ' ', (string) $this->status)),
            };
        }

        return ucwords(str_replace('_', ' ', (string) $this->status));
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending'     => 'warning',
            'assigned'    => 'info',
            'in_progress' => 'primary',
            'resolved'    => 'success',
            'closed'      => 'secondary',
            default       => 'secondary',
        };
    }

    public function getPriorityBadgeAttribute(): string
    {
        return match(strtolower($this->priority ?? '')) {
            'urgent' => 'danger',
            'high'   => 'warning',
            'medium' => 'info',
            'low'    => 'secondary',
            default  => 'secondary',
        };
    }
}
