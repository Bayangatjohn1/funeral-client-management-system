<?php

namespace App\Http\Middleware;

use App\Support\BranchScope;
use Closure;
use Illuminate\Http\Request;

class BranchScopeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $allowedBranchIds = method_exists($user, 'branchScopeIds')
            ? $user->branchScopeIds()
            : null;

        BranchScope::set($allowedBranchIds);

        if ($allowedBranchIds === []) {
            abort(403, 'No branch access configured.');
        }

        try {
            return $next($request);
        } finally {
            BranchScope::clear();
        }
    }
}
