<?php

namespace App\Filament\Widgets;

use App\Services\IdentityGatewayClient;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IdentityOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $token = session('admin.identity_token');
        $dashboard = is_string($token) ? app(IdentityGatewayClient::class)->get('dashboard/identity', $token) : null;
        $data = $dashboard['data'] ?? [];

        return [
            Stat::make('Người dùng', (string) ($data['users_total'] ?? '—'))->description((string) ($data['users_active'] ?? 0).' đang hoạt động'),
            Stat::make('Phòng ban', (string) ($data['org_units_total'] ?? '—'))->description('Cơ cấu tổ chức'),
            Stat::make('Tài khoản cần xử lý', (string) (($data['users_locked'] ?? 0) + ($data['users_must_change_password'] ?? 0)))->description('Khóa hoặc cần đổi mật khẩu'),
        ];
    }
}
