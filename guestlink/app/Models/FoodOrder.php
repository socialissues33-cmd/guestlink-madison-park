<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FoodOrder extends Model
{
    protected $fillable = [
        'room_id', 'guest_name', 'reference_number', 'status',
        'dining_mode', 'payment_method',
        'notes', 'total_amount', 'processed_by', 'guest_rating', 'guest_feedback',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'guest_rating' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->reference_number)) {
                $model->reference_number = 'ORD-' . strtoupper(Str::random(8));
            }
        });
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(FoodOrderItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(FoodOrderStatusLog::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending'    => 'warning',
            'confirmed'  => 'info',
            'preparing'  => 'primary',
            'ready'      => 'success',
            'delivered'  => 'secondary',
            'cancelled'  => 'danger',
            default      => 'secondary',
        };
    }

    public function isDineIn(): bool
    {
        return ($this->dining_mode ?? 'room_delivery') === 'dine_in';
    }

    public function isPayOnArrival(): bool
    {
        return ($this->payment_method ?? 'charge_to_room') === 'pay_on_arrival';
    }

    public function isChargeToRoom(): bool
    {
        return !$this->isPayOnArrival();
    }

    public function getDiningModeLabelAttribute(): string
    {
        return $this->isDineIn() ? 'Dine-In · Times Square' : 'Room Delivery';
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        if ($this->isPayOnArrival()) {
            return $this->isDineIn() ? 'Pay at Times Square Cashier' : 'Pay on Delivery (Cash/GCash)';
        }
        return 'Charge to Room (FO Check-Out)';
    }
}
