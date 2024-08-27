<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Pages\Auth\Register as BaseRegister;

class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getRoleFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getRoleFormComponent()
    {
        return Select::make('user_lob')->label('LOB')
            ->options([
                'CALL ENTERING' => 'CALL ENTERING',
                'ERG FOLLOW-UP' => 'ERG FOLLOW-UP',
                'DOCUMENT PROCESSING' => 'DOCUMENT PROCESSING',
            ])
            ->native(false)
            ->required();
    }
}
