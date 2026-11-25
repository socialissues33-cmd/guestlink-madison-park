<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS URLs behind cloud reverse proxies (Render, Railway, Fly.io, Cloudflare, Hostinger SSL)
        if ($this->app->environment('production') || request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        // Use Bootstrap 5 pagination globally with our custom dark-theme template
        Paginator::useBootstrapFive();

        // Tune SQLite in-memory cache & temp store for fast concurrent reads/writes
        if (config('database.default') === 'sqlite') {
            try {
                DB::statement('PRAGMA temp_store = MEMORY');
                DB::statement('PRAGMA cache_size = -16000');
            } catch (\Throwable $e) {
                // Ignore if SQLite connection is not yet initialized (e.g. build/install)
            }
        }

        // Smart per-room / per-user Rate Limiters so multiple phones sharing a
        // hotel Wi-Fi, phone hotspot, or Cloudflare Tunnel IP never collide into 429 errors.
        RateLimiter::for('guest-read', function (Request $request) {
            $token = (string) ($request->route('token') ?: 'anon');
            return Limit::perMinute(600)->by("guest-read:{$token}|{$request->ip()}");
        });

        RateLimiter::for('guest-write', function (Request $request) {
            $token = (string) ($request->route('token') ?: 'anon');
            return Limit::perMinute(120)->by("guest-write:{$token}|{$request->ip()}");
        });

        RateLimiter::for('guest-ai', function (Request $request) {
            $token = (string) ($request->route('token') ?: 'anon');
            return Limit::perMinute(60)->by("guest-ai:{$token}|{$request->ip()}");
        });

        RateLimiter::for('staff-write', function (Request $request) {
            $actor = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(240)->by("staff-write:{$actor}");
        });

        RateLimiter::for('login', function (Request $request) {
            $loginId = strtolower(trim((string) $request->input('email', '')));
            return [
                Limit::perMinute(30)->by("login:{$loginId}|{$request->ip()}"),
                Limit::perMinute(20)->by("login-acct:{$loginId}"),
            ];
        });
    }
}
