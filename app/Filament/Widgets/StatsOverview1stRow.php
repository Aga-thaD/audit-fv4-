<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use App\Models\PhoneQC;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview1stRow extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()->user_role !== 'Associate';
    }
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->icon('heroicon-o-user-group'),
            Stat::make('Total Associates', User::where('user_role', 'Associate')->count())
                ->icon('heroicon-o-user'),
            Stat::make('Total Audits', Audit::count())
                ->icon('heroicon-o-document-magnifying-glass'),
            Stat::make('Total Phone QCs', PhoneQC::count())
                ->icon('heroicon-o-phone'),
        ];
    }
}
