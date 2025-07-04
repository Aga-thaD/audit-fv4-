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
    protected int | string | array $columnSpan = 'full';

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
    $userTeams = $user->teams->pluck('slug')->toArray();
    $isInTrueSource = in_array('truesource-team', $userTeams);

    // Special case: Truesource user with LOB 'erg follow-up'
    if ($isInTrueSource && $user->lob === 'erg follow-up') {
        $query->where('pqc_lob', 'erg follow-up');

        // Further restrict if Auditor
        if ($user->user_role === 'Auditor') {
            $query->where(function ($q) use ($user) {
                $q->where('pqc_auditor', $user->name)
                  ->orWhere('user_id', $user->id);
            });
        }

        return;
    }

    // Associate: only their own audits
    if ($user->user_role === 'Associate') {
        $query->where('user_id', $user->id);
        return;
    }

    // Auditor ( who are not in erg follow-up case): audits they did or were done on them
    if ($user->user_role === 'Auditor') {
        $query->where(function ($q) use ($user) {
            $q->where('pqc_auditor', $user->name)
              ->orWhere('user_id', $user->id);
        });
        return;
    }

    // Default team-based filtering (Manager/Other)
    if ($isInTrueSource && !in_array('sos-team', $userTeams)) {
        $this->applyTrueSourceFilter($query);
    } elseif (!in_array('truesource-team', $userTeams) && in_array('sos-team', $userTeams)) {
        $this->applySOSFilter($query);
    }

    // No extra filters for admins or users in both teams
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
