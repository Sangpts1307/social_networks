<?php

namespace App\Filament\Pages;

use Filament\Support\Icons\Heroicon;

class PostModeration extends ModulePlaceholder
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?string $navigationLabel = 'Bài viết & báo cáo';

    protected static \UnitEnum|string|null $navigationGroup = 'Kiểm duyệt Social';

    protected static ?int $navigationSort = 10;

    protected static string $moduleDescription = 'Không gian xử lý bài viết bị báo cáo, quyết định kiểm duyệt và lịch sử xử lý.';

    protected static string $moduleOwner = 'social-service';

    protected static array $moduleCapabilities = ['Hàng đợi báo cáo', 'Ẩn/khôi phục bài viết', 'Ghi nhận quyết định kiểm duyệt', 'Audit theo moderator'];
}
