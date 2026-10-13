<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateGateway
{
    public function handle(Request $request, Closure $next)
    {
        $key = (string) config('identity.gateway_key');
        if (strlen($key) < 32) {
            return response()->json(['message' => 'Gateway authentication is not configured.'], 503);
        }
        if (! hash_equals($key, (string) $request->header('X-Identity-Service-Key'))) {
            return response()->json(['message' => 'Untrusted service.'], 401);
        }
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
