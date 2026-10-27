<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthenticateBranchSync
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('branch_sync.mode') === 'central', 404);
        abort_unless($request->isSecure() || app()->environment('testing'), 403, 'HTTPS is required.');
        $token = $request->bearerToken();
        abort_unless(is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token), 401);
        $peer = DB::table('branch_sync_peers')->where('branch_code', $request->header('X-Branch-Code'))
            ->where('enabled', true)->first();
        abort_unless($peer && hash_equals($peer->token_hash, hash('sha256', $token)), 401);
        abort_unless(DB::table('branches')->where('branch_code', $peer->branch_code)->where('is_active', true)->exists(), 403);
        $request->attributes->set('sync_peer', $peer);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }
}
