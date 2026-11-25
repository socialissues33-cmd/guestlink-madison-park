<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Room extends Model
{
    protected $fillable = [
        'room_number', 'room_type', 'floor', 'description', 'is_active', 'qr_token', 'checked_in_at',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'checked_in_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($room) {
            if (empty($room->qr_token)) {
                $room->qr_token = Str::random(32);
            }
        });
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function foodOrders(): HasMany
    {
        return $this->hasMany(FoodOrder::class);
    }

    public function getQrUrlAttribute(): string
    {
        return route('guest.landing', $this->qr_token);
    }
}
