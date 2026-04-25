<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OwnerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !method_exists($user, 'isOwner') || !$user->isOwner()) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
