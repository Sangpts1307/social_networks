<?php

namespace App\Filament\Pages;

use Filament\Support\Icons\Heroicon;

class SystemSettings extends ModulePlaceholder
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Cấu hình hệ thống';

    protected static \UnitEnum|string|null $navigationGroup = 'Vận hành';

    protected static ?int $navigationSort = 51;

    protected static string $moduleDescription = 'Base cấu hình vận hành. Secret và cấu hình service vẫn thuộc deployment, không sửa trực tiếp từ Admin.';

    protected static string $moduleOwner = 'admin-service';

    protected static array $moduleCapabilities = ['Trạng thái kết nối service', 'Feature flag theo organization', 'Retention policy', 'Không quản lý secret'];
}
