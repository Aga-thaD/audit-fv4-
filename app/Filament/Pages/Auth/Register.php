<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Http\Responses\Auth\RegistrationResponse;
use Filament\Pages\Page;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Auth;

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
                        $this->getHiddenRoleComponent(),
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

    protected function getHiddenRoleComponent()
    {
        return Hidden::make('user_role')
            ->default('Associate');
    }

    public function register(): ?RegistrationResponse
    {
        $data = $this->form->getState();

        $user = $this->getUserModel()::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'user_role' => $data['user_role'],
            'user_lob' => $data['user_lob'],
            // Set permissions for Associate role
            'audit_create' => false,
            'audit_view' => true,
            'audit_update' => true,
            'audit_delete' => false,
            'pqc_create' => false,
            'pqc_view' => true,
            'pqc_update' => true,
            'pqc_delete' => false,
            'user_create' => false,
            'user_view' => false,
            'user_update' => false,
            'user_delete' => false,
            // All other permissions default to false
        ]);

        Auth::login($user);

        return app(RegistrationResponse::class);
    }
}
