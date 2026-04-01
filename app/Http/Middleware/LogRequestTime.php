<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequestTime
{
    /**
     * Measure request duration and expose it via response header + log entry.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = (microtime(true) - $startedAt) * 1000;

        // Add a lightweight diagnostic header (visible in browser devtools).
        $response->headers->set('X-Response-Time-ms', number_format($durationMs, 2, '.', ''));

        // Structured log for later analysis / grepping.
        Log::info('Request time', [
            'path' => $request->path(),
            'method' => $request->method(),
            'duration_ms' => $durationMs,
        ]);

        return $response;
    }
}
