<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Rules\TeamSpanEmailRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
                        Forms\Components\FileUpload::make('avatar')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->avatar(),
                        Forms\Components\TextInput::make('name'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->rules([new TeamSpanEmailRule()]),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state)),
                        Forms\Components\Select::make('user_role')->label('Role')
                            ->options(function () {
                                $options = [
                                    'Manager' => 'Manager',
                                    'Auditor' => 'Auditor',
                                    'Associate' => 'Associate',
                                ];

                                // Only add 'Admin' option if the current user is an Admin
                                if (Auth::user()->user_role === 'Admin') {
                                    $options = ['Admin' => 'Admin'] + $options;
                                }

                                return $options;
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'Admin') {
                                    $set('audit_create', true);
                                    $set('audit_view', true);
                                    $set('audit_update', true);
                                    $set('audit_delete', true);
                                    $set('pqc_create', true);
                                    $set('pqc_view', true);
                                    $set('pqc_update', true);
                                    $set('pqc_delete', true);
                                    $set('user_create', true);
                                    $set('user_view', true);
                                    $set('user_update', true);
                                    $set('user_delete', true);
                                } elseif ($state === 'Manager') {
                                    $set('audit_create', true);
                                    $set('audit_view', true);
                                    $set('audit_update', true);
                                    $set('audit_delete', true);
                                    $set('pqc_create', true);
                                    $set('pqc_view', true);
                                    $set('pqc_update', true);
                                    $set('pqc_delete', true);
                                    $set('user_create', true);
                                    $set('user_view', true);
                                    $set('user_update', true);
                                    $set('user_delete', true);
                                } elseif ($state === 'Auditor') {
                                    $set('audit_create', true);
                                    $set('audit_view', true);
                                    $set('audit_update', true);
                                    $set('audit_delete', true);
                                    $set('pqc_create', true);
                                    $set('pqc_view', true);
                                    $set('pqc_update', true);
                                    $set('pqc_delete', true);
                                    $set('user_create', false);
                                    $set('user_view', false);
                                    $set('user_update', false);
                                    $set('user_delete', false);
                                } elseif ($state === 'Associate') {
                                    $set('audit_create', false);
                                    $set('audit_view', true);
                                    $set('audit_update', true);
                                    $set('audit_delete', false);
                                    $set('pqc_create', false);
                                    $set('pqc_view', true);
                                    $set('pqc_update', true);
                                    $set('pqc_delete', false);
                                    $set('user_create', false);
                                    $set('user_view', false);
                                    $set('user_update', false);
                                    $set('user_delete', false);
                                }
                            })
                            ->native(false),
                            Forms\Components\Select::make('user_lob')->label('LOB')
                            ->options(function () {
                                $user = Auth::user();
                                $isSOSTeam = $user->teams->contains('slug', 'sos-team');
                                $isTrueSourceTeam = $user->teams->contains('slug', 'truesource-team');
                                $isCintasTeam = $user->teams->contains('slug', 'cintas-ar-team');
                        
                        
                                if ($isSOSTeam) {
                                    return [
                                        'CUSTOMER SERVICE REP' => 'CUSTOMER SERVICE REP',
                                        'ACCOUNTS RECEIVABLE/PAYABLE' => 'ACCOUNTS RECEIVABLE/PAYABLE',
                                    ];
                                } elseif ($isTrueSourceTeam) {
                                    return [
                                        'CALL ENTERING' => 'CALL ENTERING',
                                        'ERG FOLLOW-UP' => 'ERG FOLLOW-UP',
                                        'DOCUMENT PROCESSING' => 'DOCUMENT PROCESSING',
                                    ];
                                } elseif ($isCintasTeam) {
                                    return [
                                        'CINTAS ACCOUNTS RECEIVABLE' => 'CINTAS ACCOUNTS RECEIVABLE',
                                    ];
                                } else {
                                    // For users not in any specific team
                                    return [
                                        'CALL ENTERING' => 'CALL ENTERING',
                                        'ERG FOLLOW-UP' => 'ERG FOLLOW-UP',
                                        'DOCUMENT PROCESSING' => 'DOCUMENT PROCESSING',
                                        'CUSTOMER SERVICE REP' => 'CUSTOMER SERVICE REP',
                                        'ACCOUNTS RECEIVABLE/PAYABLE' => 'ACCOUNTS RECEIVABLE/PAYABLE',
                                        'CINTAS ACCOUNTS RECEIVABLE' => 'CINTAS ACCOUNTS RECEIVABLE',
                                    ];
                                }
                            })
                            ->multiple(),

                        Forms\Components\TextInput::make('eo_number')->label('EO NUMBER')
                        ->visible(function () {
                            $user = Auth::user();
                            $isCintasTeam = $user->teams->contains('slug', 'cintas-ar-team');
                            if($isCintasTeam)
                            {
                                return true;
                            }
                            else
                            {
                                return false;
                            }
                        })
                    ]),
                Forms\Components\Section::make('Permissions')
                    ->schema([
                        Forms\Components\Section::make('Audit Form')
                            ->schema([
                                Forms\Components\Toggle::make('audit_create')->label('Create'),
                                Forms\Components\Toggle::make('audit_view')->label('View'),
                                Forms\Components\Toggle::make('audit_update')->label('Update'),
                                Forms\Components\Toggle::make('audit_delete')->label('Delete'),
                            ])->columnSpan(1),
                        Forms\Components\Section::make('Phone QC')
                            ->schema([
                                Forms\Components\Toggle::make('pqc_create')->label('Create'),
                                Forms\Components\Toggle::make('pqc_view')->label('View'),
                                Forms\Components\Toggle::make('pqc_update')->label('Update'),
                                Forms\Components\Toggle::make('pqc_delete')->label('Delete'),
                            ])->columnSpan(1),
                        Forms\Components\Section::make('User')
                            ->schema([
                                Forms\Components\Toggle::make('user_create')->label('Create'),
                                Forms\Components\Toggle::make('user_view')->label('View'),
                                Forms\Components\Toggle::make('user_update')->label('Update'),
                                Forms\Components\Toggle::make('user_delete')->label('Delete'),
                            ])->columnSpan(1),
                    ])->columns(3)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')->label(''),
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
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (Auth::user()->user_role !== 'Admin') {
                    $query->where('user_role', '!=', 'Admin');
                }
            });
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
