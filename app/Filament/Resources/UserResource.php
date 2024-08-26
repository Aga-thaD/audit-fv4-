<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name'),
                        Forms\Components\TextInput::make('email'),
                        Forms\Components\TextInput::make('password'),
                        Forms\Components\Select::make('user_role')->label('Role')
                            ->options([
                                'Admin' => 'Admin',
                                'Auditor' => 'Auditor',
                                'Associate' => 'Associate',
                            ]),
                        Forms\Components\Select::make('user_lob')->label('LOB')
                            ->options([
                                'CALL ENTERING' => 'CALL ENTERING',
                                'ERG FOLLOW-UP' => 'ERG FOLLOW-UP',
                                'DOCUMENT PROCESSING' => 'DOCUMENT PROCESSING',
                            ]),
                    ]),
                Forms\Components\Section::make('Permissions')
                    ->schema([
                        Forms\Components\Section::make('Audit Form')
                            ->schema([
                                Forms\Components\Toggle::make('audit_create')->label('Create'),
                                Forms\Components\Toggle::make('audit_view')->label('View'),
                                Forms\Components\Toggle::make('audit_update')->label('Update'),
                                Forms\Components\Toggle::make('audit_delete')->label('Delete'),
                            ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('user_role')->label('Role'),
                Tables\Columns\TextColumn::make('user_lob')->label('LOB'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
