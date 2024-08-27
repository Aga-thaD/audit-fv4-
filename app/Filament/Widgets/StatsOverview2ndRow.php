<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview2ndRow extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        return [
            Stat::make('Pending Audits', Audit::where('aud_status', 'Pending')->count())
                ->icon('heroicon-o-sparkles'),
            Stat::make('Disputed Audits', Audit::where('aud_status', 'Disputed')->count())
                ->icon('heroicon-o-x-circle'),
            Stat::make('Acknowledged Audits', Audit::where('aud_status', 'Acknowledged')->count())
                ->icon('heroicon-o-check-circle'),
        ];
    }
}
