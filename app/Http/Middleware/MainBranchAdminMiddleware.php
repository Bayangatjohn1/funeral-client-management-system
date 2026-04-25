<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MainBranchAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !method_exists($user, 'isMainBranchAdmin') || !$user->isMainBranchAdmin()) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
