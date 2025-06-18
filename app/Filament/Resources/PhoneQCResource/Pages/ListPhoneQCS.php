<?php

namespace App\Filament\Resources\PhoneQCResource\Pages;

use App\Filament\Resources\PhoneQCResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListPhoneQCS extends ListRecords
{
    protected static string $resource = PhoneQCResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $user = Auth::user();
        $tabs = [];

        // Always include the 'All Phone QCs' tab
        $tabs['all'] = Tab::make('All Phone QCs');

        // Define the mapping between LOB values and tab keys
        $lobTabMapping = [
            'ERG FOLLOW-UP' => 'erg_follow_up',
            // Add other LOBs here if needed for Phone QCs
        ];

        // Add tabs based on user's LOB
        foreach ($user->user_lob as $lob) {
            if (isset($lobTabMapping[$lob])) {
                $tabKey = $lobTabMapping[$lob];
                $tabs[$tabKey] = Tab::make($lob)
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('pqc_lob', $lob));
            }
        }

        // If the user is an Admin or Manager, show all tabs
        if (in_array($user->user_role, ['Admin', 'Manager'])) {
            $tabs = [
                'all' => Tab::make('All Phone QCs'),
                'erg_follow_up' => Tab::make('ERG Follow-Up')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('pqc_lob', 'ERG FOLLOW-UP')),
                // Add other LOBs here if needed for Phone QCs
            ];
        }

        return $tabs;
    }
}
