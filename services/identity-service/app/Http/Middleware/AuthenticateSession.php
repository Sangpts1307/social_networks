<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthenticateSession
{
    public function handle(Request $request, Closure $next, string $mode = 'full')
    {
        $token = $request->bearerToken();
        if (! $token || ! preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return DB::transaction(function () use ($token, $request, $next, $mode) {
            $hash = hash('sha256', $token);
            $candidate = UserSession::where('session_hash', $hash)->first();
            if (! $candidate) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            // Consistent user -> session lock order serializes login/password change/revocation.
            $user = User::whereKey($candidate->user_id)->lockForUpdate()->first();
            $session = UserSession::whereKey($candidate->id)->lockForUpdate()->first();
            if (! $user || ! $session || $session->revoked_at || $session->expires_at->isPast()
                || (int) $session->password_version !== (int) $user->password_version
                || $user->status !== 'active' || $user->locked_at || $user->locked_until) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            if ($mode === 'full' && $user->must_change_password_at) {
                return response()->json(['message' => 'Password change required.', 'code' => 'PASSWORD_CHANGE_REQUIRED'], 403);
            }
            $request->setUserResolver(fn () => $user);
            $request->attributes->set('identity_session', $session);
            $session->forceFill(['last_seen_at' => now()])->save();

            return $next($request);
        });
    }
}
