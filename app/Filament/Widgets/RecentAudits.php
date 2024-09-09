<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use App\Models\Team;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentAudits extends BaseWidget
{
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No Audits')
            ->query(
                Audit::query()->latest()->limit(5)
            )
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                $this->applyUserFilters($query, $user);
            })
            ->columns([
                Tables\Columns\TextColumn::make('aud_case_number')
                    ->label('Case/WO #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lob')
                    ->label('LOB')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('aud_auditor')
                    ->label('Auditor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('aud_error_category')
                    ->label('Error Category'),
                Tables\Columns\TextColumn::make('aud_type_of_error')
                    ->label('Error Type'),
                Tables\Columns\TextColumn::make('aud_date')
                    ->label('Audit Date')
                    ->date(),
                Tables\Columns\TextColumn::make('aud_status')
                    ->label('Status'),
            ]);
    }

    protected function applyUserFilters(Builder $query, $user): void
    {
        if ($user->user_role === 'Associate') {
            $query->where('user_id', $user->id);
            return;
        }

        $userTeams = $user->teams->pluck('slug')->toArray();

        if (in_array('truesource-team', $userTeams) && !in_array('sos-team', $userTeams)) {
            $this->applyTrueSourceFilter($query);
        } elseif (!in_array('truesource-team', $userTeams) && in_array('sos-team', $userTeams)) {
            $this->applySOSFilter($query);
        }
        // If user is in both teams or neither (admin), no additional filtering is needed
    }

    protected function applyTrueSourceFilter(Builder $query): void
    {
        $trueSourceTeam = Team::where('slug', 'truesource-team')->first();
        if ($trueSourceTeam) {
            $trueSourceUserIds = $trueSourceTeam->members()->pluck('users.id')->toArray();
            $query->whereIn('user_id', $trueSourceUserIds);
        }
    }

    protected function applySOSFilter(Builder $query): void
    {
        $sosTeam = Team::where('slug', 'sos-team')->first();
        if ($sosTeam) {
            $sosUserIds = $sosTeam->members()->pluck('users.id')->toArray();
            $query->whereIn('user_id', $sosUserIds);
        }
    }
}
