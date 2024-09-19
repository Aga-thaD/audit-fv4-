<?php

namespace App\Filament\Resources\PhoneQCResource\Pages;

use App\Filament\Resources\PhoneQCResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePhoneQC extends CreateRecord
{
    protected static string $resource = PhoneQCResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
