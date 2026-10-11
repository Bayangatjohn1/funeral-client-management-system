<?php

namespace App\Http\Middleware;

use App\Support\IdleSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureIdleSession
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()) return $next($request);
        $last = $request->session()->get('idle_last_activity');
        if (! is_numeric($last) || now()->timestamp >= (int) $last + IdleSession::seconds($request->user())) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $message = 'Your session expired due to inactivity. Please sign in again.';
            $request->session()->flash('status', $message);
            return $request->expectsJson()
                ? response()->json(['message' => $message, 'expired' => true, 'login_url' => route('login')], 401)
                : redirect()->route('login');
        }
        // Background JSON/AJAX requests, including status polling, never extend the idle deadline.
        if (! $request->routeIs('session.*', 'logout') && ! $request->expectsJson() && ! $request->ajax()) {
            $request->session()->put('idle_last_activity', now()->timestamp);
        }
        $response = $next($request);
        if ($request->user() && $request->session()->has('idle_last_activity')) {
            $response->headers->set('X-Session-Expires-At', (string) IdleSession::state($request)['expires_at']);
        }
        return $response;
    }
}
