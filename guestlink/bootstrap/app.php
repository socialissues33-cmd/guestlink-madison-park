<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'guest/*',
        ]);
        $middleware->alias([
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419) {
                return redirect('/login')->with('error', 'Your login session expired. Please sign in again.');
            }
            if ($e->getStatusCode() === 429) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success'   => false,
                        'throttled' => true,
                        'message'   => 'Too many requests in a short moment. Retrying automatically...',
                    ], 429);
                }
                return back()->with('error', 'High traffic detected — please wait a second and try again.');
            }
        });
    })->create();
