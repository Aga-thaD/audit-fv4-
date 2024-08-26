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
                Forms\Components\Select::make('lob')
                    ->label('LOB')
                    ->options([
                        'CALL ENTERING' => 'CALL ENTERING',
                        'ERG FOLLOW-UP' => 'ERG FOLLOW-UP',
                        'DOCUMENT PROCESSING' => 'DOCUMENT PROCESSING',
                    ])
                    ->reactive()
                    ->afterStateUpdated(function (callable $set) {
                        $set('user_id', null);
                        $set('aud_error_category', null);
                        $set('aud_error_type', null);
                    })
                    ->required(),
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
                Forms\Components\Select::make('aud_error_category')
                    ->label('Error Category')
                    ->options([
                        'CRITICAL' => 'CRITICAL',
                        'MAJOR' => 'MAJOR',
                        'MINOR' => 'MINOR',
                    ])
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('aud_error_type', null))
                    ->required(),
                Forms\Components\Select::make('aud_type_of_error')
                    ->label('Error Type')
                    ->options(function (callable $get) {
                        $lob = $get('lob');
                        $category = $get('aud_error_category');
                        if (!$lob || !$category) {
                            return [];
                        }
                        return self::getErrorTypes()[$lob][$category] ?? [];
                    })
                    ->reactive()
                    ->searchable()
                    ->required(),
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

    protected static function getErrorTypes(): array
    {
        return [
            'CALL ENTERING' => [
                'CRITICAL' => [
                    'Incorrect location' => 'Incorrect location',
                    'Missed email request' => 'Missed email request',
                    'Back charge' => 'Back charge',
                ],
                'MAJOR' => [
                    '3rd party procedure issue' => '3rd party procedure issue',
                    'Duplicate case issue' => 'Duplicate case issue',
                    'Email not sent to AH' => 'Email not sent to AH',
                    'Incorrect case owner' => 'Incorrect case owner',
                    'Incorrect customer' => 'Incorrect customer',
                    'Incorrect ETA date' => 'Incorrect ETA date',
                    'Incorrect NTE' => 'Incorrect NTE',
                    'Incorrect PO' => 'Incorrect PO',
                    'Incorrect priority code' => 'Incorrect priority code',
                    'Incorrect Service Category' => 'Incorrect Service Category',
                    'Incorrect skill code' => 'Incorrect skill code',
                    'Incorrect skill trade' => 'Incorrect skill trade',
                    'Missing/Incorrect case notes' => 'Missing/Incorrect case notes',
                    'Missing/Incorrect verbiage' => 'Missing/Incorrect verbiage',
                    'PO not accepted' => 'PO not accepted',
                    'Missing account Team' => 'Missing account Team',
                ],
                'MINOR' => [
                    'Incomplete Portal details' => 'Incomplete Portal details',
                    'Incorrect/Missing email attachment' => 'Incorrect/Missing email attachment',
                    'Missing Add contact Info' => 'Missing Add contact Info',
                    'Missing Chatter' => 'Missing Chatter',
                    'Missing work order' => 'Missing work order',
                    'Missing/Incorrect case notes' => 'Missing/Incorrect case notes',
                    'Security or Safety Box' => 'Security or Safety Box',
                    'Special Instruction Not Followed' => 'Special Instruction Not Followed',
                ],
            ],
            'ERG FOLLOW-UP' => [
                'CRITICAL' => [
                    'Back charge' => 'Back charge',
                    'Rudeness' => 'Rudeness',
                    'Incorrect debrief' => 'Incorrect debrief',
                    'Incorrect work order IVR' => 'Incorrect work order IVR',
                ],
                'MAJOR' => [
                    'Incorrect Next Action' => 'Incorrect Next Action',
                    'Incorrect Case Owner' => 'Incorrect Case Owner',
                    'Incomplete Documentation' => 'Incomplete Documentation',
                    'Missed Follow-Up' => 'Missed Follow-Up',
                    'Customer IVR issue' => 'Customer IVR issue',
                    'Incorrect recipients' => 'Incorrect recipients',
                    'Incorrect ETA' => 'Incorrect ETA',
                    'Service order subtype error' => 'Service order subtype error',
                    'End of month procedure' => 'End of month procedure',
                    'Incorrect Repair Details' => 'Incorrect Repair Details',
                    'Left WOs in Active cases' => 'Left WOs in Active cases',
                    'Incomplete Follow up' => 'Incomplete Follow up',
                    'Inaccurate Documentation' => 'Inaccurate Documentation',
                ],
                'MINOR' => [
                    'Gameplan' => 'Gameplan',
                    'Failed to execute bulk ff-up' => 'Failed to execute bulk ff-up',
                    'Email to Service Team/AH' => 'Email to Service Team/AH',
                    'Invalid Follow-up' => 'Invalid Follow-up',
                    'Call ownership' => 'Call ownership',
                ],
            ],
            'DOCUMENT PROCESSING' => [
                'CRITICAL' => [
                    'Incorrect PO amount' => 'Incorrect PO amount',
                    'Rudeness' => 'Rudeness',
                ],
                'MAJOR' => [
                    'Incorrect document attached' => 'Incorrect document attached',
                    'Missed to upload PPW' => 'Missed to upload PPW',
                    'Documents not combined' => 'Documents not combined',
                    'Reason code' => 'Reason code',
                ],
                'MINOR' => [
                    'Invalid transfer' => 'Invalid transfer',
                    'Incorrect document label' => 'Incorrect document label',
                    'Duplicate attachment' => 'Duplicate attachment',
                ],
            ],
        ];
    }
}
