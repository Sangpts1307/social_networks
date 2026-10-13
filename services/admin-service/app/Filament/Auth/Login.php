<?php

namespace App\Filament\Auth;

use App\Auth\RemoteIdentityUser;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        if ($user instanceof RemoteIdentityUser && $user->requiresPasswordChange()) {
            throw ValidationException::withMessages([
                'data.email' => 'Tài khoản này phải đổi mật khẩu lần đầu ở Web app trước khi vào Cổng quản trị.',
            ]);
        }

        return parent::isUserAllowedToAccessPanel($user);
    }
}
