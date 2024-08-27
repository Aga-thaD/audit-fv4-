<?php

namespace App\Filament\Widgets;

use App\Models\PhoneQC;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview3rdRow extends BaseWidget
{
    protected static ?int $sort = 3;
    protected function getStats(): array
    {
        return [
            Stat::make('Pending Phone QCs', PhoneQC::where('pqc_status', 'Pending')->count())
                ->icon('heroicon-o-sparkles'),
            Stat::make('Disputed Phone QCs', PhoneQC::where('pqc_status', 'Disputed')->count())
                ->icon('heroicon-o-x-circle'),
            Stat::make('Acknowledged Phone QCs', PhoneQC::where('pqc_status', 'Acknowledged')->count())
                ->icon('heroicon-o-check-circle'),
        ];
    }
}
