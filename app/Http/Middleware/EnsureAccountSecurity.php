<?php

namespace App\Http\Middleware;

use App\Support\AccountSecurity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAccountSecurity
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) return $next($request);

        // A stale login form must validate its submitted credentials, not resume another tab's login.
        $pendingMfa = $user->requiresMfa() && $request->session()->get('mfa_user_id') !== $user->id;
        if ($request->routeIs('login.store') || ($request->routeIs('login') && $pendingMfa)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return $next($request);
        }

        $fingerprint = AccountSecurity::fingerprint($user);
        $stored = $request->session()->get('account_security');
        // Initial rollout invalidates older authenticated sessions; HTTP tests supply a fingerprint.
        if (! is_string($stored) || ! hash_equals($fingerprint, $stored)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Account access changed. Please sign in again.']);
        }

        if ($user->requiresMfa() && ! $request->routeIs('security.*', 'session.*', 'logout')) {
            if ($user->two_factor_confirmed_at && $request->session()->get('mfa_user_id') !== $user->id) {
                return redirect()->route('security.challenge');
            }
            if (! $user->two_factor_confirmed_at) {
                return redirect()->route('security.setup');
            }
            if ($user->must_change_password) return redirect()->route('security.password');
            if ($request->session()->get('mfa_user_id') !== $user->id) {
                return redirect()->route('security.challenge');
            }
        }

        return $next($request);
    }
}
