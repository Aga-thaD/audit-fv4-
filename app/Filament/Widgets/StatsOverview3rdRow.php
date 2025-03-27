<?php

namespace App\Filament\Widgets;

use App\Models\PhoneQC;
use App\Models\Team;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class StatsOverview3rdRow extends BaseWidget
{
    public static function canView(): bool
    {
        $user = Auth::user();
        $userTeams = $user->teams->pluck('slug')->toArray();

        // Hide the resource if the user is only in the SOS team
        if (in_array('sos-team', $userTeams) || in_array('cintas-ar-team', $userTeams)) {
            return false;
        }
        else
        {
            return true;
        }
    }

    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $user = Auth::user();
        $phoneQCQuery = $this->getFilteredPhoneQCQuery($user);

        return [
            Stat::make('Pending Phone QCs', $phoneQCQuery->clone()->where('pqc_status', 'Pending')->count())
                ->icon('heroicon-o-sparkles'),
            Stat::make('Disputed Phone QCs', $phoneQCQuery->clone()->where('pqc_status', 'Disputed')->count())
                ->icon('heroicon-o-x-circle'),
            Stat::make('Acknowledged Phone QCs', $phoneQCQuery->clone()->where('pqc_status', 'Acknowledged')->count())
                ->icon('heroicon-o-check-circle'),
        ];
    }

    protected function getFilteredPhoneQCQuery($user): Builder
    {
        $query = PhoneQC::query();

        if ($user->user_role === 'Associate') {
            return $query->where('user_id', $user->id);
        }

        $userTeams = $user->teams->pluck('slug')->toArray();

        if (in_array('truesource-team', $userTeams) && !in_array('sos-team', $userTeams)) {
            return $this->getTrueSourcePhoneQCs($query);
        }

        if (!in_array('truesource-team', $userTeams) && in_array('sos-team', $userTeams)) {
            return $this->getSOSPhoneQCs($query);
        }

        // If user is in both teams or neither (admin), return all Phone QCs
        return $query;
    }

    protected function getTrueSourcePhoneQCs(Builder $query): Builder
    {
        $trueSourceTeam = Team::where('slug', 'truesource-team')->first();
        if ($trueSourceTeam) {
            $trueSourceUserIds = $trueSourceTeam->members()->pluck('users.id')->toArray();
            return $query->whereIn('user_id', $trueSourceUserIds);
        }
        return $query;
    }

    protected function getSOSPhoneQCs(Builder $query): Builder
    {
        $sosTeam = Team::where('slug', 'sos-team')->first();
        if ($sosTeam) {
            $sosUserIds = $sosTeam->members()->pluck('users.id')->toArray();
            return $query->whereIn('user_id', $sosUserIds);
        }
        return $query;
    }
}
