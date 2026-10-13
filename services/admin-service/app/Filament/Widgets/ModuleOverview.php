<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ModuleOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Social', 'Sẵn sàng')->description('Kiểm duyệt bài viết và báo cáo'),
            Stat::make('Learning', 'Sẵn sàng')->description('Hỏi đáp, tài liệu và khóa học'),
            Stat::make('Giao tiếp', 'Sẵn sàng')->description('Hội thoại, thông báo và broadcast'),
            Stat::make('AI & tri thức', 'Sẵn sàng')->description('Curation và index tri thức'),
        ];
    }
}
