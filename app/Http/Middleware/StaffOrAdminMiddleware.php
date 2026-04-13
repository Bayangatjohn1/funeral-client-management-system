<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StaffOrAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        $role = (string) auth()->user()->role;
        if (!in_array($role, ['staff', 'admin'], true)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}

