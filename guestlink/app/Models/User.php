<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'department',
        'is_active',
        'theme_preference',
        'bot_auto_reply_enabled',
        'sidebar_collapsed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'password'               => 'hashed',
            'is_active'              => 'boolean',
            'bot_auto_reply_enabled' => 'boolean',
            'sidebar_collapsed'      => 'boolean',
        ];
    }

    /**
     * Determine whether the Madi AI Live Chat auto-reply bot should default to ON
     * for incoming guest requests & messages, based on Front Desk / Admin configuration.
     */
    public static function isBotAutoReplyDefaultEnabled(): bool
    {
        $cached = \Illuminate\Support\Facades\Cache::get('guestlink_hotel_bot_auto_reply');
        if (is_bool($cached)) {
            return $cached;
        }

        return !static::where('is_active', true)
            ->where('bot_auto_reply_enabled', false)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['front_desk', 'admin', 'owner']);
            })
            ->exists();
    }
}
