<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
            ->visible(function ($record) {
                $currentUser = auth()->user();
                $targetUserRole = \App\Models\User::find($record->id)->user_role;
            
                if ($currentUser->user_role === 'Admin') {
                    return true;
                }
            
                if ($currentUser->user_role === 'Manager') {
                    return $targetUserRole !== 'Manager';
                }
            
                return false;
            })            
    ];
}
}