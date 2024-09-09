<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use App\Models\Team;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class StatsOverview2ndRow extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = Auth::user();
        $auditQuery = $this->getFilteredAuditQuery($user);

        return [
            Stat::make('Pending Audits', $auditQuery->clone()->where('aud_status', 'Pending')->count())
                ->icon('heroicon-o-sparkles'),
            Stat::make('Disputed Audits', $auditQuery->clone()->where('aud_status', 'Disputed')->count())
                ->icon('heroicon-o-x-circle'),
            Stat::make('Acknowledged Audits', $auditQuery->clone()->where('aud_status', 'Acknowledged')->count())
                ->icon('heroicon-o-check-circle'),
        ];
    }

    protected function getFilteredAuditQuery($user): Builder
    {
        $query = Audit::query();

        if ($user->user_role === 'Associate') {
            return $query->where('user_id', $user->id);
        }

        $userTeams = $user->teams->pluck('slug')->toArray();

        if (in_array('truesource-team', $userTeams) && !in_array('sos-team', $userTeams)) {
            return $this->getTrueSourceAudits($query);
        }

        if (!in_array('truesource-team', $userTeams) && in_array('sos-team', $userTeams)) {
            return $this->getSOSAudits($query);
        }

        // If user is in both teams or neither (admin), return all audits
        return $query;
    }

    protected function getTrueSourceAudits(Builder $query): Builder
    {
        $trueSourceTeam = Team::where('slug', 'truesource-team')->first();
        if ($trueSourceTeam) {
            $trueSourceUserIds = $trueSourceTeam->members()->pluck('users.id')->toArray();
            return $query->whereIn('user_id', $trueSourceUserIds);
        }
        return $query;
    }

    protected function getSOSAudits(Builder $query): Builder
    {
        $sosTeam = Team::where('slug', 'sos-team')->first();
        if ($sosTeam) {
            $sosUserIds = $sosTeam->members()->pluck('users.id')->toArray();
            return $query->whereIn('user_id', $sosUserIds);
        }
        return $query;
    }
}
