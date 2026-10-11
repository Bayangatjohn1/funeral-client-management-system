<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->is_active) {
            Auth::logout();
            return redirect('/login')
                ->withErrors(['email' => 'Your account is inactive.']);
        }

        if (Auth::check() && (Auth::user()->isBranchAdmin() || Auth::user()->isStaff())
            && Auth::user()->branch_id && ! Auth::user()->branch?->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login')
                ->withErrors(['email' => 'Your assigned branch is currently inactive. Please contact the system administrator.']);
        }

        return $next($request);
    }
}
