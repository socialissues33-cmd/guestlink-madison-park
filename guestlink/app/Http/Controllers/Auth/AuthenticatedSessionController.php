<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = strtolower(trim($validated['email']));
        $password   = $validated['password'];
        $remember   = $request->boolean('remember');

        // Resolve user safely by exact email, exact username, or strict alphanumeric email prefix
        $userQuery = \App\Models\User::where(function ($q) use ($loginInput) {
            $q->whereRaw('LOWER(email) = ?', [$loginInput])
              ->orWhereRaw('LOWER(COALESCE(username, "")) = ?', [$loginInput]);

            if (!str_contains($loginInput, '@') && preg_match('/^[a-z0-9._-]+$/', $loginInput)) {
                $escapedPrefix = addcslashes($loginInput, '%_\\');
                $q->orWhereRaw("LOWER(email) LIKE ? ESCAPE '\\'", [$escapedPrefix . '@%']);
            }
        })->orderByRaw(
            'CASE WHEN LOWER(email) = ? OR LOWER(COALESCE(username, "")) = ? THEN 0 ELSE 1 END',
            [$loginInput, $loginInput]
        );

        $user = $userQuery->first();

        $authenticated = $user && \Illuminate\Support\Facades\Hash::check($password, $user->password);

        if (!$authenticated || !$user) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->onlyInput('email');
        }

        if ($user->is_active === false) {
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact the administrator.',
            ])->onlyInput('email');
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
