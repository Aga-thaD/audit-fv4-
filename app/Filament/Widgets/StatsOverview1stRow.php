<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use App\Models\PhoneQC;
use App\Models\User;
use App\Models\Team;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StatsOverview1stRow extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()->user_role !== 'Associate';
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $userTeams = $user->teams->pluck('slug')->toArray();

        $userQuery = User::query();
        $auditQuery = Audit::query();
        $phoneQCQuery = PhoneQC::query();

        if (empty($userTeams)) {
            Log::warning('User has no teams', ['user_id' => $user->id]);
            return $this->getAllStats($userQuery, $auditQuery, $phoneQCQuery);
        }

        if (in_array('truesource-team', $userTeams) && !in_array('sos-team', $userTeams)) {
            return $this->getTrueSourceStats($userQuery, $auditQuery, $phoneQCQuery);
        }

        if (!in_array('truesource-team', $userTeams) && in_array('sos-team', $userTeams)) {
            return $this->getSOSStats($userQuery, $auditQuery, $phoneQCQuery);
        }

        return $this->getAllStats($userQuery, $auditQuery, $phoneQCQuery);
    }

    private function getAllStats($userQuery, $auditQuery, $phoneQCQuery): array
    {
        return [
            Stat::make('Total Users', $userQuery->count())
                ->icon('heroicon-o-user-group'),
            Stat::make('Total Associates', $userQuery->where('user_role', 'Associate')->count())
                ->icon('heroicon-o-user'),
            Stat::make('Total Audits', $auditQuery->count())
                ->icon('heroicon-o-document-magnifying-glass'),
            Stat::make('Total Phone QCs', $phoneQCQuery->count())
                ->icon('heroicon-o-phone'),
        ];
    }

    private function getTrueSourceStats($userQuery, $auditQuery, $phoneQCQuery): array
    {
        $trueSourceTeam = Team::where('slug', 'truesource-team')->first();

        if (!$trueSourceTeam) {
            Log::error('TrueSource team not found');
            return $this->getAllStats($userQuery, $auditQuery, $phoneQCQuery);
        }

        $trueSourceUserIds = $trueSourceTeam->members()->pluck('users.id')->toArray();

        return [
            Stat::make('TrueSource Users', $userQuery->whereHas('teams', function ($query) {
                $query->where('slug', 'truesource-team');
            })->count())
                ->icon('heroicon-o-user-group'),
            Stat::make('TrueSource Associates', $userQuery->where('user_role', 'Associate')
                ->whereHas('teams', function ($query) {
                    $query->where('slug', 'truesource-team');
                })->count())
                ->icon('heroicon-o-user'),
            Stat::make('TrueSource Audits', $auditQuery->whereIn('user_id', $trueSourceUserIds)->count())
                ->icon('heroicon-o-document-magnifying-glass'),
            Stat::make('TrueSource Phone QCs', $phoneQCQuery->whereIn('user_id', $trueSourceUserIds)->count())
                ->icon('heroicon-o-phone'),
        ];
    }

    private function getSOSStats($userQuery, $auditQuery, $phoneQCQuery): array
    {
        $sosTeam = Team::where('slug', 'sos-team')->first();

        if (!$sosTeam) {
            Log::error('SOS team not found');
            return $this->getAllStats($userQuery, $auditQuery, $phoneQCQuery);
        }

        $sosUserIds = $sosTeam->members()->pluck('users.id')->toArray();

        return [
            Stat::make('SOS Users', $userQuery->whereHas('teams', function ($query) {
                $query->where('slug', 'sos-team');
            })->count())
                ->icon('heroicon-o-user-group'),
            Stat::make('SOS Associates', $userQuery->where('user_role', 'Associate')
                ->whereHas('teams', function ($query) {
                    $query->where('slug', 'sos-team');
                })->count())
                ->icon('heroicon-o-user'),
            Stat::make('SOS Audits', $auditQuery->whereIn('user_id', $sosUserIds)->count())
                ->icon('heroicon-o-document-magnifying-glass'),
            Stat::make('SOS Phone QCs', $phoneQCQuery->whereIn('user_id', $sosUserIds)->count())
                ->icon('heroicon-o-phone'),
        ];
    }
}
