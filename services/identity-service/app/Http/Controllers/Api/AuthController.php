<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserSession;
use App\Services\AdminAuditLogger;
use App\Services\AdminAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController
{
    public function __construct(
        private readonly AdminAuditLogger $audit,
    ) {}

    public static function view(User $user): array
    {
        $authorization = app(AdminAuthorization::class);

        return [
            'id' => $user->id,
            'organization_id' => $user->organization_id,
            'email' => $user->email,
            'display_name' => $user->display_name,
            'must_change_password' => $user->must_change_password_at !== null,
            'authorization' => $authorization->context($user),
            'permissions' => $authorization->permissions($user),
        ];
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|max:1024',
        ]);

        return DB::transaction(function () use ($request, $data) {
            // Email-only baseline: never guess a tenant when an address is ambiguous.
            $users = User::whereRaw('LOWER(email) = ?', [strtolower(trim($data['email']))])->limit(2)->lockForUpdate()->get();
            if ($users->count() !== 1) {
                Hash::check($data['password'], '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');

                $this->audit->record(
                    request: $request,
                    actor: null,
                    eventType: 'auth.login.failed',
                    entityType: User::class,
                    entityId: null,
                    payload: ['email' => $data['email'], 'reason' => 'email_not_found'],
                    action: 'LOGIN',
                    riskLevel: 'medium',
                    status: 'failure',
                    module: 'identity',
                    actorName: $data['email'],
                    actorEmail: $data['email']
                );

                return response()->json(['message' => 'Thông tin đăng nhập không hợp lệ.'], 401);
            }

            $user = $users->first();
            if ($user->status !== 'active' || $user->locked_at || $user->locked_until) {
                $this->audit->record(
                    request: $request,
                    actor: $user,
                    eventType: 'auth.login.blocked',
                    entityType: User::class,
                    entityId: $user->id,
                    payload: ['status' => $user->status, 'locked_at' => $user->locked_at],
                    action: 'LOGIN',
                    riskLevel: 'high',
                    status: 'blocked',
                    module: 'identity'
                );

                return response()->json(['message' => 'Tài khoản không khả dụng.'], 403);
            }

            if (! Hash::check($data['password'], $user->password_hash)) {
                $user->failed_login_count = min(10, $user->failed_login_count + 1);
                $isLockedNow = false;
                if ($user->failed_login_count >= config('identity.lockout_attempts', 10)) {
                    $user->locked_at = now();
                    $isLockedNow = true;
                }
                $user->save();

                $this->audit->record(
                    request: $request,
                    actor: $user,
                    eventType: $isLockedNow ? 'auth.account.locked' : 'auth.login.failed',
                    entityType: User::class,
                    entityId: $user->id,
                    payload: [
                        'failed_attempts' => $user->failed_login_count,
                        'max_attempts' => config('identity.lockout_attempts', 10),
                    ],
                    action: 'LOGIN',
                    riskLevel: $isLockedNow ? 'high' : ($user->failed_login_count >= 3 ? 'medium' : 'normal'),
                    status: 'failure',
                    module: 'identity'
                );

                return response()->json(['message' => 'Thông tin đăng nhập không hợp lệ.'], 401);
            }

            $user->forceFill(['failed_login_count' => 0, 'last_login_at' => now()])->save();
            $token = bin2hex(random_bytes(32));
            $expires = now()->addMinutes(config('identity.session_minutes', 120));
            UserSession::create([
                'user_id' => $user->id,
                'session_hash' => hash('sha256', $token),
                'password_version' => $user->password_version,
                'expires_at' => $expires,
                'last_seen_at' => now(),
            ]);

            // Ghi nhận audit log đăng nhập thành công
            $this->audit->record(
                request: $request,
                actor: $user,
                eventType: 'auth.login.success',
                entityType: User::class,
                entityId: $user->id,
                payload: ['display_name' => $user->display_name, 'email' => $user->email],
                action: 'LOGIN',
                riskLevel: 'normal',
                status: 'success',
                module: 'identity'
            );

            return response()->json([
                'token_type' => 'Bearer',
                'access_token' => $token,
                'expires_at' => $expires->toISOString(),
                'user' => self::view($user),
            ]);
        });
    }

    public function session(Request $request)
    {
        return response()->json(['user' => self::view($request->user())]);
    }

    public function me(Request $request)
    {
        return $this->session($request);
    }

    public function logout(Request $request)
    {
        $session = $request->attributes->get('identity_session');
        if ($session) {
            $session->forceFill(['revoked_at' => now()])->save();
        }

        $user = $request->user();
        if ($user) {
            $this->audit->record(
                request: $request,
                actor: $user,
                eventType: 'auth.logout',
                entityType: User::class,
                entityId: $user->id,
                payload: ['session_id' => $session?->id],
                action: 'LOGOUT',
                riskLevel: 'normal',
                status: 'success',
                module: 'identity'
            );
        }

        return response()->noContent();
    }

    public function logoutAll(Request $request)
    {
        $user = $request->user();
        UserSession::where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);

        $this->audit->record(
            request: $request,
            actor: $user,
            eventType: 'auth.logout_all',
            entityType: User::class,
            entityId: $user->id,
            payload: ['action' => 'revoked_all_active_sessions'],
            action: 'LOGOUT',
            riskLevel: 'medium',
            status: 'success',
            module: 'identity'
        );

        return response()->noContent();
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string|max:1024',
            'password' => [
                'required',
                'string',
                'max:72',
                function ($attribute, $value, $fail) {
                    if (strlen($value) > 72) {
                        $fail('Mật khẩu tối đa 72 byte.');
                    }
                },
                'confirmed',
                Password::min($request->user()->must_change_password_at !== null ? 10 : 12)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password_hash)) {
            $this->audit->record(
                request: $request,
                actor: $user,
                eventType: 'auth.password.change_failed',
                entityType: User::class,
                entityId: $user->id,
                payload: ['reason' => 'wrong_current_password'],
                action: 'UPDATE_PASSWORD',
                riskLevel: 'medium',
                status: 'failure',
                module: 'identity'
            );

            return response()->json(['message' => 'Mật khẩu hiện tại không đúng.'], 422);
        }

        if (Hash::check($data['password'], $user->password_hash)) {
            return response()->json(['message' => 'Mật khẩu mới phải khác mật khẩu cũ.'], 422);
        }

        $user->forceFill([
            'password_hash' => Hash::make($data['password']),
            'password_version' => $user->password_version + 1,
            'must_change_password_at' => null,
            'password_changed_at' => now(),
        ])->save();

        UserSession::where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);

        $this->audit->record(
            request: $request,
            actor: $user,
            eventType: 'auth.password.changed',
            entityType: User::class,
            entityId: $user->id,
            payload: ['password_version' => $user->password_version],
            action: 'UPDATE_PASSWORD',
            riskLevel: 'medium',
            status: 'success',
            module: 'identity'
        );

        return response()->json(['message' => 'Đã đổi mật khẩu. Vui lòng đăng nhập lại.']);
    }
}
