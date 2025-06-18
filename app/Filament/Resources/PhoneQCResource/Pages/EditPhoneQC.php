<?php

namespace App\Filament\Resources\PhoneQCResource\Pages;

use App\Filament\Resources\PhoneQCResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPhoneQC extends EditRecord
{
    protected static string $resource = PhoneQCResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
