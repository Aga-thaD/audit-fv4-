<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('avatar')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('avatars')
                    ->visibility('public')
                    ->avatar()
                    ->deleteUploadedFileUsing(function ($record) {
                        if ($record instanceof \App\Models\User) {
                            $record->deleteAvatar();
                        }
                    }),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }
}
