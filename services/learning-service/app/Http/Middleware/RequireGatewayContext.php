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
            return response()->json(['message' => 'Learning gateway authentication is not configured.'], 503);
        }
        if (! hash_equals($key, (string) $request->header('x-learning-gateway-key'))) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $userId = (string) $request->header('x-authenticated-user-id');
        $organizationId = (string) $request->header('x-authenticated-organization-id');
        if (! $this->isUuid($userId) || ! $this->isUuid($organizationId)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $permissions = array_values(array_filter(
            explode(' ', (string) $request->header('x-authenticated-permissions')),
            fn (string $permission): bool => (bool) preg_match('/^[a-z0-9][a-z0-9._-]{0,119}$/', $permission),
        ));

        $authorization = json_decode((string) $request->header('x-authorization-context'), true);
        if (! is_array($authorization) || ($authorization['version'] ?? null) !== 1
            || ! is_array($authorization['grants'] ?? null) || ! is_array($authorization['org_unit_ids'] ?? null)) {
            return response()->json(['message' => 'Authorization context is required.'], 401);
        }

        $request->attributes->set('learning_actor', [
            'user_id' => $userId,
            'organization_id' => $organizationId,
            'permissions' => $permissions,
            'authorization' => $authorization,
        ]);

        return $next($request);
    }

    private function isUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $value);
    }
}
