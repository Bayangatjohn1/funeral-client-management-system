<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $phoneProof = $request->routeIs('payment-proof.phone', 'payment-proof.phone-upload');
        $response->headers->set('Referrer-Policy', $phoneProof || $request->routeIs('password.reset', 'password.store') ? 'no-referrer' : 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', $phoneProof ? 'camera=(self), microphone=(), geolocation=()' : 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
