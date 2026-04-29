<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user?->isOwner() && $request->routeIs('admin.payments.index', 'admin.payment-monitoring')) {
            if (Route::has('owner.analytics')) {
                return redirect()->route('owner.analytics');
            }

            abort(403, 'Unauthorized');
        }

        if (!$user || !method_exists($user, 'isAdmin') || !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
