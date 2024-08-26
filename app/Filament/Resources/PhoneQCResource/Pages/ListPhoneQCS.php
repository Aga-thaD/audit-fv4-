<?php

namespace App\Filament\Resources\PhoneQCResource\Pages;

use App\Filament\Resources\PhoneQCResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPhoneQCS extends ListRecords
{
    protected static string $resource = PhoneQCResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
