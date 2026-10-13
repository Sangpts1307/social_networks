<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserSession;
use App\Services\AdminAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController
{
    public static function view(User $user): array
    {
        $authorization = app(AdminAuthorization::class);

        return ['id' => $user->id, 'organization_id' => $user->organization_id,
            'email' => $user->email, 'display_name' => $user->display_name,
            'must_change_password' => $user->must_change_password_at !== null,
            'permissions' => $authorization->permissions($user)];
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|string|email|max:255', 'password' => 'required|string|max:1024']);

        return DB::transaction(function () use ($data) {
            // Email-only baseline: never guess a tenant when an address is ambiguous.
            $users = User::whereRaw('LOWER(email) = ?', [strtolower(trim($data['email']))])->limit(2)->lockForUpdate()->get();
            if ($users->count() !== 1) {
                Hash::check($data['password'], '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');

                return response()->json(['message' => 'Thông tin đăng nhập không hợp lệ.'], 401);
            }
            $user = $users->first();
            if ($user->status !== 'active' || $user->locked_at || $user->locked_until) {
                return response()->json(['message' => 'Tài khoản không khả dụng.'], 403);
            }
            if (! Hash::check($data['password'], $user->password_hash)) {
                $user->failed_login_count = min(10, $user->failed_login_count + 1);
                if ($user->failed_login_count >= config('identity.lockout_attempts')) {
                    $user->locked_at = now();
                }
                $user->save();

                // Return, don't throw: failed-attempt changes must commit.
                return response()->json(['message' => 'Thông tin đăng nhập không hợp lệ.'], 401);
            }
            $user->forceFill(['failed_login_count' => 0, 'last_login_at' => now()])->save();
            $token = bin2hex(random_bytes(32));
            $expires = now()->addMinutes(config('identity.session_minutes'));
            UserSession::create(['user_id' => $user->id, 'session_hash' => hash('sha256', $token),
                'password_version' => $user->password_version, 'expires_at' => $expires,
                'last_seen_at' => now()]);

            return response()->json(['token_type' => 'Bearer', 'access_token' => $token,
                'expires_at' => $expires->toISOString(), 'user' => self::view($user)]);
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
        $request->attributes->get('identity_session')->forceFill(['revoked_at' => now()])->save();

        return response()->noContent();
    }

    public function logoutAll(Request $request)
    {
        UserSession::where('user_id', $request->user()->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);

        return response()->noContent();
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate(['current_password' => 'required|string|max:1024',
            'password' => ['required', 'string', 'max:72', function ($attribute, $value, $fail) {
                if (strlen($value) > 72) {
                    $fail('Mật khẩu tối đa 72 byte.');
                }
            }, 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password_hash)) {
            return response()->json(['message' => 'Mật khẩu hiện tại không đúng.'], 422);
        }
        if (Hash::check($data['password'], $user->password_hash)) {
            return response()->json(['message' => 'Mật khẩu mới phải khác mật khẩu cũ.'], 422);
        }
        $user->forceFill(['password_hash' => Hash::make($data['password']), 'password_version' => $user->password_version + 1,
            'password_changed_at' => now(), 'must_change_password_at' => null])->save();
        UserSession::where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);

        return response()->json(['message' => 'Đã đổi mật khẩu. Vui lòng đăng nhập lại.']);
    }
}
