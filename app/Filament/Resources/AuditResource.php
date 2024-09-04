<?php

namespace App\Filament\Resources;

use App\Filament\Exports\AuditExporter;
use App\Filament\Resources\AuditResource\Pages;
use App\Filament\Resources\AuditResource\RelationManagers;
use App\Models\Audit;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('lob')
                                    ->required()
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
                                    ->required()
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
                                    ->required()
                                    ->label('Auditor')
                                    ->default(fn () => Auth::user()->name)
                                    ->readOnly(),
                                Forms\Components\DatePicker::make('aud_date')
                                    ->required()
                                    ->label('Audit Date')
                                    ->default(fn () => Carbon::today()->toDateString())
                                    ->format('Y-m-d')  // Changed format to Y-m-d
                                    ->displayFormat('m/d/Y') // This is for display only
                                    ->readOnly(),
                                Forms\Components\DatePicker::make('aud_date_processed')->label('Date Processed')
                                    ->required()
                                    ->format('Y-m-d')  // Changed format to Y-m-d
                                    ->displayFormat('m/d/Y'),  // This is for display only
                                Forms\Components\Select::make('aud_time_processed')->label('Time Processed')
                                    ->required()
                                    ->options([
                                        'Prime' => 'Prime',
                                        'Afterhours' => 'Afterhours',
                                    ])
                                    ->native(false),
                                Forms\Components\TextInput::make('aud_case_number')->label('Case/WO #')
                                    ->required(),
                                Forms\Components\Select::make('aud_audit_type')->label('Type of Audit')
                                    ->required()
                                    ->options([
                                        'Internal' => 'Internal',
                                        'Client' => 'Client',
                                    ])
                                    ->native(false),
                                Forms\Components\TextInput::make('aud_customer')->label('Customer')
                                    ->required(),
                                Forms\Components\Select::make('aud_area_hit')->label('Area Hit')
                                    ->required()
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
                            ]),
                        Forms\Components\RichEditor::make('aud_feedback')->label('Feedback')
                            ->required(),
                        Forms\Components\FileUpload::make('aud_screenshot')->label('Screenshot'),
                        Forms\Components\Hidden::make('aud_status')
                            ->default('Pending'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\ExportAction::make()->label('Export Audits')
                    ->exporter(AuditExporter::class)
            ])
            ->columns([
                Tables\Columns\TextColumn::make('aud_case_number')->label('Case/WO #'),
                Tables\Columns\TextColumn::make('aud_error_category')->label('Error Category'),
                Tables\Columns\TextColumn::make('lob')->label('LOB'),
                Tables\Columns\TextColumn::make('user.name')->label('Name'),
                Tables\Columns\TextColumn::make('aud_auditor')->label('Auditor'),
                Tables\Columns\TextColumn::make('aud_customer')->label('Customer'),
                Tables\Columns\TextColumn::make('aud_date')->label('Audit Date'),
                Tables\Columns\TextColumn::make('aud_status')->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Disputed' => 'danger',
                        'Acknowledged' => 'success',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->visible(fn () => in_array(Auth::user()->user_role, ['Admin', 'Manager', 'Auditor'])),
                    Tables\Actions\Action::make('Dispute')
                        ->label('Dispute')
                        ->icon('heroicon-o-exclamation-circle')
                        ->color('warning')
                        ->form([
                            Forms\Components\Textarea::make('aud_associate_feedback')->label('Reason for Dispute')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\FileUpload::make('aud_associate_screenshot')->label('Screenshot')
                                ->maxFiles(3),
                        ])
                        ->action(function (Audit $record, array $data) {
                            $record->update([
                                'aud_status' => 'Disputed',
                                'aud_associate_feedback' => $data['aud_associate_feedback'],
                                'aud_associate_screenshot' => $data['aud_associate_screenshot'],
                                'aud_dispute_timestamp' => now(), // Add this line to set the dispute timestamp
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn (Audit $record) =>
                            Auth::user()->user_role === 'Associate' &&
                            $record->aud_status === 'Pending'
                        ),
                    Tables\Actions\Action::make('Acknowledge')
                        ->label('Acknowledge')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Audit $record) {
                            $record->update(['aud_status' => 'Acknowledged']);
                        })
                        ->requiresConfirmation()
                        ->visible(fn (Audit $record) =>
                            Auth::user()->user_role === 'Associate' &&
                            $record->aud_status === 'Pending'
                        ),
                    Tables\Actions\Action::make('Mark as Pending')
                        ->label('Mark as Pending')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->action(function (Audit $record) {
                            $record->update(['aud_status' => 'Pending']);
                        })
                        ->requiresConfirmation()
                        ->visible(fn (Audit $record) =>
                            in_array(Auth::user()->user_role, ['Admin', 'Manager', 'Auditor']) &&
                            $record->aud_status === 'Disputed'
                        ),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if ($user->user_role === 'Associate') {
                    $query->where('user_id', $user->id);
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
            'index' => Pages\ListAudits::route('/'),
            'create' => Pages\CreateAudit::route('/create'),
            'edit' => Pages\EditAudit::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make()
                    ->schema([
                        Tabs\Tab::make('Audit Details')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                TextEntry::make('lob')->label('LOB'),
                                                TextEntry::make('user.name')->label('Name'),
                                                TextEntry::make('aud_auditor')->label('Auditor'),
                                                TextEntry::make('aud_date')->label('Audit Date')->date('m/d/Y'),
                                                TextEntry::make('aud_date_processed')->label('Date Processed')->date('m/d/Y'),
                                                TextEntry::make('aud_time_processed')->label('Time Processed'),
                                                TextEntry::make('aud_case_number')->label('Case/WO #'),
                                                TextEntry::make('aud_audit_type')->label('Type of Audit'),
                                                TextEntry::make('aud_customer')->label('Customer'),
                                                TextEntry::make('aud_area_hit')->label('Area Hit'),
                                                TextEntry::make('aud_error_category')->label('Error Category'),
                                                TextEntry::make('aud_type_of_error')->label('Error Type'),
                                            ]),
                                        TextEntry::make('aud_feedback')->label('Feedback')->html(),
                                        ImageEntry::make('aud_screenshot')->label('Screenshot'),
                                        TextEntry::make('aud_status')->label('Status'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Dispute Remarks')
                            ->schema([
                                TextEntry::make('aud_associate_feedback')->label('Reason for Dispute'),
                                ImageEntry::make('aud_associate_screenshot')->label('Screenshot'),
                                TextEntry::make('aud_dispute_timestamp')->label('Dispute Filed On')
                                    ->dateTime('m/d/Y H:i:s'), // Add this line to display the dispute timestamp
                            ])->visible(fn ($record) => $record->aud_status === 'Disputed'),
                    ])->columnSpanFull(),
            ]);
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
