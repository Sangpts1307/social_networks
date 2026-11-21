<?php

namespace App\Http\Controllers;

use App\Auth\RemoteIdentityUser;
use App\Services\IdentityGatewayClient;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SsoController extends Controller
{
    public function __invoke(Request $request, IdentityGatewayClient $identity): RedirectResponse
    {
        $token = $request->query('token');

        if (! is_string($token) || trim($token) === '') {
            return redirect()->route('filament.admin.auth.login');
        }

        $user = $identity->session($token);

        if (! is_array($user) || empty($user['id'])) {
            return redirect()->route('filament.admin.auth.login')
                ->withErrors(['data.email' => 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.']);
        }

        $remoteUser = new RemoteIdentityUser($user);

        if ($remoteUser->requiresPasswordChange()) {
            return redirect()->route('filament.admin.auth.login')
                ->withErrors(['data.email' => 'Tài khoản này phải đổi mật khẩu lần đầu ở Web app trước khi vào Cổng quản trị.']);
        }

        $panel = Filament::getPanel('admin');
        if (! $remoteUser->canAccessPanel($panel)) {
            return redirect()->route('filament.admin.auth.login')
                ->withErrors(['data.email' => 'Tài khoản của bạn không có quyền truy cập Cổng quản trị.']);
        }

        $request->session()->regenerate();
        $request->session()->put('admin.identity_token', $token);
        $request->session()->put('admin.identity_user', $user);

        Auth::guard('admin')->login($remoteUser);

        $redirect = $request->query('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/admin') && ! str_starts_with($redirect, '/admin/sso') && ! str_starts_with($redirect, '/admin/login')) {
            return redirect($redirect);
        }

        return redirect()->intended(Filament::getUrl());
    }
}
