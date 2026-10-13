<?php

namespace App\Filament\Pages;

use Filament\Support\Icons\Heroicon;

class AiKnowledgeCuration extends ModulePlaceholder
{
    protected string $view = 'filament.pages.ai-knowledge-curation';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Curation tri thức AI';

    protected static \UnitEnum|string|null $navigationGroup = 'AI & tri thức';

    protected static ?int $navigationSort = 40;

    protected static string $moduleDescription = 'Base reviewer cho tri thức do AI đề xuất, nguồn tham chiếu và trạng thái index.';

    protected static string $moduleOwner = 'ai-knowledge-service';

    protected static array $moduleCapabilities = ['Hàng đợi đề xuất', 'Duyệt/từ chối nguồn', 'Re-index tri thức', 'Theo dõi provenance'];
}
