<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhoneQCResource\Pages;
use App\Filament\Resources\PhoneQCResource\RelationManagers;
use App\Models\Audit;
use App\Models\PhoneQC;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PhoneQCResource extends Resource
{
    protected static ?string $model = PhoneQC::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $modelLabel = 'Phone QC';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\Hidden::make('pqc_status')
                            ->default('Pending'),
                        Forms\Components\Select::make('pqc_lob')->label('LOB')
                            ->options([
                                "ERG FOLLOW-UP" => "ERG FOLLOW-UP",
                            ])
                            ->reactive(),
                        Forms\Components\Select::make('user_id')->label('Name')
                            ->options(function (callable $get) {
                                $lob = $get('pqc_lob');
                                if (!$lob) {
                                    return User::all()->pluck('name', 'id');
                                }
                                return User::where('user_lob', $lob)->pluck('name', 'id');
                            })
                            ->reactive()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('pqc_auditor')
                            ->label('Auditor')
                            ->default(fn () => Auth::user()->name)
                            ->readOnly(),
                        Forms\Components\DatePicker::make('pqc_audit_date')
                            ->label('Audit Date')
                            ->default(fn () => Carbon::today()->toDateString())
                            ->format('Y-m-d')  // Changed format to Y-m-d
                            ->displayFormat('m/d/Y')  // This is for display only
                            ->readOnly(),
                        Forms\Components\DatePicker::make('pqc_date_processed')->label('Date Processed')
                            ->format('Y-m-d')  // Changed format to Y-m-d
                            ->displayFormat('m/d/Y'),  // This is for display only
                        Forms\Components\Select::make('pqc_time_processed')->label('Time Processed')
                            ->options([
                                'Prime' => 'Prime',
                                'Afterhours' => 'Afterhours',
                            ])
                            ->native(false),
                        Forms\Components\Select::make('pqc_type_of_call')->label('Type of Call')
                            ->options([
                                'Technician' => 'Technician',
                                'Store' => 'Store',
                            ])
                            ->native(false),
                    ])->columnSpan(1),
                Forms\Components\Section::make('Remarks')
                    ->schema([
                        Forms\Components\Textarea::make('pqc_call_summary')->label('Call Summary'),
                        Forms\Components\Textarea::make('pqc_strengths')->label('Strength/s'),
                        Forms\Components\Textarea::make('pqc_opportunities')->label('Opportunities'),
                        Forms\Components\FileUpload::make('pqc_call_recording')->label('Call Recording'),
                    ])->columnSpan(1),
                Forms\Components\Section::make('Scorecard')
                    ->schema([
                        Forms\Components\TextInput::make('pqc_score')->label('Score')
                            ->default(100)
                            ->readOnly(),
                        Forms\Components\Repeater::make('pqc_scorecard')->label('Critical to Quality (CTQ)')
                            ->schema([
                                Forms\Components\Select::make('category')->label('Category')
                                    ->options([
                                        'Opening and Closing Spiel' => 'Opening and Closing Spiel',
                                        'Customer Experience' => 'Customer Experience',
                                        'Procedural Adherence' => 'Procedural Adherence',
                                    ])
                                    ->reactive()
                                    ->native(false),
                                Forms\Components\Select::make('sub_category')->label('Sub-Category')
                                    ->options(function (callable $get) {
                                        $category = $get('category');
                                        switch ($category) {
                                            case 'Opening and Closing Spiel':
                                                return [
                                                    'Name' => 'Name',
                                                    'Branding' => 'Branding',
                                                    'Compliance - Recorded Line' => 'Compliance - Recorded Line',
                                                    'Thank you and Goodbye' => 'Thank you and Goodbye',
                                                ];
                                            case 'Customer Experience':
                                                return [
                                                    'Active Listening / Comprehension / Communication' => 'Active Listening / Comprehension / Communication',
                                                    'Empathy' => 'Empathy',
                                                    'Professionalism' => 'Professionalism',
                                                ];
                                            case 'Procedural Adherence':
                                                return [
                                                    'Proper Probing' => 'Proper Probing',
                                                    'Process Mastery' => 'Process Mastery',
                                                    'Sense of Urgency' => 'Sense of Urgency',
                                                    'Accuracy' => 'Accuracy',
                                                ];
                                            default:
                                                return [];
                                        }
                                    })
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        $weightageMap = [
                                            'Name' => 3,
                                            'Branding' => 3,
                                            'Compliance - Recorded Line' => 5,
                                            'Thank you and Goodbye' => 2,
                                            'Active Listening / Comprehension / Communication' => 10,
                                            'Empathy' => 10,
                                            'Professionalism' => 20,
                                            'Proper Probing' => 20,
                                            'Process Mastery' => 20,
                                            'Sense of Urgency' => 10,
                                            'Accuracy' => 10,
                                        ];
                                        $weightage = $weightageMap[$state] ?? 0;
                                        $set('pqc_weightage', $weightage);

                                        // Recalculate the total score
                                        $scorecard = $get('../../pqc_scorecard');
                                        $totalWeightage = collect($scorecard)->sum('pqc_weightage');
                                        $set('../../pqc_score', 100 - $totalWeightage);
                                    })
                                    ->reactive()
                                    ->searchable(),
                                Forms\Components\TextInput::make('pqc_weightage')->label('Weightage')
                                    ->readOnly()
                            ])->columns(3)
                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action
                                    ->after(function (Forms\Components\Repeater $component, callable $get, callable $set) {
                                        // Recalculate the total score after deletion
                                        $scorecard = $get('pqc_scorecard');
                                        $totalWeightage = collect($scorecard)->sum('pqc_weightage');
                                        $set('pqc_score', 100 - $totalWeightage);
                                    })
                            )
                            ->addActionLabel('Add CTQ')
                    ]),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pqc_lob')->label('LOB'),
                Tables\Columns\TextColumn::make('user.name')->label('Name'),
                Tables\Columns\TextColumn::make('pqc_auditor')->label('Auditor'),
                Tables\Columns\TextColumn::make('pqc_audit_date')->label('Audit Date'),
                Tables\Columns\TextColumn::make('pqc_score')->label('Score'),
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
                            Forms\Components\Textarea::make('pqc_associate_feedback')->label('Reason for Dispute')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\FileUpload::make('pqc_associate_screenshot')->label('Screenshot')
                                ->maxFiles(3),
                        ])
                        ->action(function (PhoneQC $record, array $data) {
                            $record->update([
                                'pqc_status' => 'Disputed',
                                'pqc_associate_feedback' => $data['pqc_associate_feedback'],
                                'pqc_associate_screenshot' => $data['pqc_associate_screenshot'],
                                'pqc_dispute_timestamp' => now(), // Add this line to set the dispute timestamp
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn (PhoneQC $record) =>
                            Auth::user()->user_role === 'Associate' &&
                            $record->pqc_status === 'Pending'
                        ),
                    Tables\Actions\Action::make('Acknowledge')
                        ->label('Acknowledge')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (PhoneQC $record) {
                            $record->update(['pqc_status' => 'Acknowledged']);
                        })
                        ->requiresConfirmation()
                        ->visible(fn (PhoneQC $record) =>
                            Auth::user()->user_role === 'Associate' &&
                            $record->pqc_status === 'Pending'
                        ),
                    Tables\Actions\Action::make('Mark as Pending')
                        ->label('Mark as Pending')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->action(function (PhoneQC $record) {
                            $record->update(['pqc_status' => 'Pending']);
                        })
                        ->requiresConfirmation()
                        ->visible(fn (PhoneQC $record) =>
                            in_array(Auth::user()->user_role, ['Admin', 'Manager', 'Auditor']) &&
                            $record->pqc_status === 'Disputed'
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make()
                    ->schema([
                        Tabs\Tab::make('Details')
                            ->schema([
                                TextEntry::make('pqc_lob')->label('LOB'),
                                TextEntry::make('user.name')->label('Name'),
                                TextEntry::make('pqc_auditor')->label('Auditor'),
                                TextEntry::make('pqc_audit_date')->label('Audit Date'),
                                TextEntry::make('pqc_date_processed')->label('Date Processed'),
                                TextEntry::make('pqc_time_processed')->label('Time Processed'),
                                TextEntry::make('pqc_type_of_call')->label('Type of Call'),

                            ]),
                        Tabs\Tab::make('Remarks')
                            ->schema([
                                TextEntry::make('pqc_call_summary')->label('Call Summary'),
                                TextEntry::make('pqc_strengths')->label('Strength/s'),
                                TextEntry::make('pqc_opportunities')->label('Opportunities'),
                                ImageEntry::make('pqc_call_recording')->label('Call Recording'),
                            ]),
                        Tabs\Tab::make('Scorecard')
                            ->schema([
                                TextEntry::make('pqc_score')->label('Score'),
                                RepeatableEntry::make('pqc_scorecard')->label('')
                                    ->schema([
                                        TextEntry::make('category'),
                                        TextEntry::make('sub_category')->label('Sub-Category'),
                                        TextEntry::make('pqc_weightage')->label('Weightage'),
                                    ])
                            ])
                    ])->columnSpanFull()
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
            'index' => Pages\ListPhoneQCS::route('/'),
            'create' => Pages\CreatePhoneQC::route('/create'),
            'edit' => Pages\EditPhoneQC::route('/{record}/edit'),
        ];
    }
}
