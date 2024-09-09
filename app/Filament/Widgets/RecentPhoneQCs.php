<?php

namespace App\Filament\Widgets;

use App\Models\PhoneQC;
use App\Models\Team;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentPhoneQCs extends BaseWidget
{
    protected static ?string $heading = 'Recent Phone QCs';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No Phone QCs')
            ->query(
                PhoneQC::query()->latest()->limit(5)
            )
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                $this->applyUserFilters($query, $user);
            })
            ->columns([
                Tables\Columns\TextColumn::make('pqc_lob')
                    ->label('LOB')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pqc_auditor')
                    ->label('Auditor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pqc_type_of_call')
                    ->label('Type of Call'),
                Tables\Columns\TextColumn::make('pqc_audit_date')
                    ->label('Audit Date')
                    ->date('m/d/Y'),
                Tables\Columns\TextColumn::make('pqc_score')
                    ->label('Score'),
                Tables\Columns\TextColumn::make('pqc_status')
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
