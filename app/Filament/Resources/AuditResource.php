<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditResource\Pages;
use App\Filament\Resources\AuditResource\RelationManagers;
use App\Models\Audit;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('lob')->label('LOB')
                    ->options([
                        'Call Entering' => 'Call Entering',
                        'Document Processing' => 'Document Processing',
                        // Add more options as needed
                    ])
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('user_id', null)),
                Forms\Components\Select::make('user_id')->label('Name')
                    ->options(function (callable $get) {
                        $lob = $get('lob');
                        if (!$lob) {
                            return User::all()->pluck('name', 'id');
                        }
                        return User::where('user_lob', $lob)->pluck('name', 'id');
                    })
                    ->reactive()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('aud_auditor')
                    ->label('Auditor')
                    ->default(fn () => Auth::user()->name)
                    ->readOnly(),
                Forms\Components\TextInput::make('aud_date')->label('Audit Date')
                    ->default(fn () => Carbon::today())
                    ->readOnly(),
                Forms\Components\DatePicker::make('aud_date_processed')->label('Date Processed'),
                Forms\Components\Select::make('aud_time_processed')->label('Time Processed')
                    ->options([
                        'Prime' => 'Prime',
                        'Afterhours' => 'Afterhours',
                    ])
                    ->native(false),
                Forms\Components\TextInput::make('aud_case_number')->label('Case/WO #'),
                Forms\Components\Select::make('aud_audit_type')->label('Type of Audit')
                    ->options([
                        'Internal' => 'Internal',
                        'Client' => 'Client',
                    ])
                    ->native(false),
                Forms\Components\TextInput::make('aud_customer')->label('Customer'),
                Forms\Components\Select::make('aud_area_hit')->label('Area Hit')
                    ->options([
                        'Work Order Level' => 'Work Order Level',
                        'Case Level' => 'Case Level',
                        'Portal' => 'Portal',
                        'Emails' => 'Emails',
                        'Others' => 'Others',
                        'Not Applicable' => 'Not Applicable',
                    ])
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lob')->label('LOB'),
                Tables\Columns\TextColumn::make('user.name')->label('Name'),
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
            'index' => Pages\ListAudits::route('/'),
            'create' => Pages\CreateAudit::route('/create'),
            'edit' => Pages\EditAudit::route('/{record}/edit'),
        ];
    }
}
