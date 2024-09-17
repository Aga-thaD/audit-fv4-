<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListAudits extends ListRecords
{
    protected static string $resource = AuditResource::class;

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

        // Always include the 'All Audits' tab
        $tabs['all'] = Tab::make('ALL');

        // Define the mapping between LOB values and tab keys
        $lobTabMapping = [
            'CALL ENTERING' => 'call_entering',
            'ERG FOLLOW-UP' => 'erg_follow_up',
            'DOCUMENT PROCESSING' => 'document_processing',
            'CUSTOMER SERVICE REP' => 'customer_service',
            'ACCOUNTS RECEIVABLE/PAYABLE' => 'accounts',
        ];

        // Add tabs based on user's LOB
        foreach ($user->user_lob as $lob) {
            if (isset($lobTabMapping[$lob])) {
                $tabKey = $lobTabMapping[$lob];
                $tabs[$tabKey] = Tab::make($lob)
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('lob', $lob));
            }
        }

        // If the user is an Admin, show all tabs
        if ($user->user_role === 'Admin') {
            $tabs = [
                'all' => Tab::make('All'),
                'call_entering' => Tab::make('Call Entering')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('lob', 'CALL ENTERING')),
                'erg_follow_up' => Tab::make('ERG Follow-Up')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('lob', 'ERG FOLLOW-UP')),
                'document_processing' => Tab::make('Document Processing')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('lob', 'DOCUMENT PROCESSING')),
                'customer_service' => Tab::make('Customer Service Rep')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('lob', 'CUSTOMER SERVICE REP')),
                'accounts' => Tab::make('Accounts Receivable/Payable')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('lob', 'ACCOUNTS RECEIVABLE/PAYABLE')),
            ];
        }

        return $tabs;
    }
}
