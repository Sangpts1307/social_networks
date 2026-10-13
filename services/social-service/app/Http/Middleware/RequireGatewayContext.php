<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireGatewayContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) config('services.gateway_key');
        if (strlen($key) < 32) {
            return response()->json(['message' => 'Social gateway authentication is not configured.', 'code' => 'SERVICE_UNAVAILABLE', 'errors' => (object) []], 503)->header('Cache-Control', 'no-store');
        }
        if (! hash_equals($key, (string) $request->header('x-social-gateway-key'))) {
            return response()->json(['message' => 'Unauthenticated.', 'code' => 'UNAUTHENTICATED', 'errors' => (object) []], 401)->header('Cache-Control', 'no-store');
        }

        $userId = (string) $request->header('x-authenticated-user-id');
        $organizationId = (string) $request->header('x-authenticated-organization-id');
        if (! $this->isUuid($userId) || ! $this->isUuid($organizationId)) {
            return response()->json(['message' => 'Unauthenticated.', 'code' => 'UNAUTHENTICATED', 'errors' => (object) []], 401)->header('Cache-Control', 'no-store');
        }

        $permissions = array_values(array_filter(
            explode(' ', (string) $request->header('x-authenticated-permissions')),
            fn (string $permission): bool => (bool) preg_match('/^[a-z0-9][a-z0-9._-]{0,119}$/', $permission),
        ));

        $request->attributes->set('social_actor', [
            'user_id' => $userId,
            'organization_id' => $organizationId,
            'permissions' => $permissions,
        ]);

        return $next($request);
    }

    private function isUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $value);
    }
}
