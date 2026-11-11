<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHistoricalCasesEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('funeral.historical_cases_enabled', false), 404);

        return $next($request);
    }
}
