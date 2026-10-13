<?php

namespace App\Filament\Pages;

use Filament\Support\Icons\Heroicon;

class AuditLogs extends ModulePlaceholder
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Nhật ký kiểm toán';

    protected static \UnitEnum|string|null $navigationGroup = 'Vận hành';

    protected static ?int $navigationSort = 50;

    protected static string $moduleDescription = 'Base đọc audit tập trung. Identity audit đã sẵn sàng; aggregate đa service sẽ nối bằng events.';

    protected static string $moduleOwner = 'identity-service → admin read model';

    protected static array $moduleCapabilities = ['Tra cứu hành động quản trị', 'Filter theo actor/entity', 'Correlation ID', 'Read model đa service'];
}
