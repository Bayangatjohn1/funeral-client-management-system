<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        $throttleEmail = (string) session('login_throttle_email', '');
        $key = \Illuminate\Support\Str::transliterate(\Illuminate\Support\Str::lower($throttleEmail).'|'.request()->ip());
        $seconds = $throttleEmail !== '' && \Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)
            ? \Illuminate\Support\Facades\RateLimiter::availableIn($key) : 0;
        return view('auth.login', ['throttleEmail' => $throttleEmail, 'throttleSeconds' => $seconds]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        \App\Support\IdleSession::start($request);

        $user = $request->user();
        $request->session()->put('account_security', \App\Support\AccountSecurity::fingerprint($user));
        $request->session()->forget(['mfa_user_id', 'mfa_setup_secret', 'mfa_recovery_display']);
        $request->session()->forget('login_throttle_email');

        if (!$user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Your account is inactive. Please contact the administrator.',
            ]);
        }

        if (($user->isBranchAdmin() || $user->isStaff()) && $user->branch_id && ! $user->branch?->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Your assigned branch is currently inactive. Please contact the system administrator.',
            ]);
        }

        if ($user->isOwner()) {
            return redirect()->intended('/owner');
        }

        if ($user->requiresMfa()) {
            return redirect()->route($user->two_factor_confirmed_at ? 'security.challenge' : 'security.setup');
        }

        if ($user->isAdmin()) {
            return redirect()->intended('/admin');
        }

        if ($user->role === 'staff') {
            return redirect()->intended('/staff');
        }

        return redirect()->intended(route('dashboard', absolute: false));

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
