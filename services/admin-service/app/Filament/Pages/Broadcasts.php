<?php

namespace App\Filament\Pages;

use Filament\Support\Icons\Heroicon;

class Broadcasts extends ModulePlaceholder
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?string $navigationLabel = 'Thông báo & broadcast';

    protected static \UnitEnum|string|null $navigationGroup = 'Giao tiếp';

    protected static ?int $navigationSort = 31;

    protected static string $moduleDescription = 'Base tạo broadcast, duyệt template và theo dõi phân phối thông báo.';

    protected static string $moduleOwner = 'notification-service';

    protected static array $moduleCapabilities = ['Template notification', 'Soạn broadcast', 'Target audience theo scope', 'Lịch sử gửi'];
}
