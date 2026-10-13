<?php

namespace App\Filament\Pages;

use Filament\Support\Icons\Heroicon;

class Conversations extends ModulePlaceholder
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Hội thoại';

    protected static \UnitEnum|string|null $navigationGroup = 'Giao tiếp';

    protected static ?int $navigationSort = 30;

    protected static string $moduleDescription = 'Base tra cứu hội thoại và áp dụng moderation theo policy của Chat.';

    protected static string $moduleOwner = 'chat-service';

    protected static array $moduleCapabilities = ['Tra cứu conversation', 'Xử lý báo cáo chat', 'Ẩn message theo policy', 'Audit moderator'];
}
