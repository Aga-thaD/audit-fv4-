<?php

namespace App\Filament\Resources;

use App\Filament\Exports\PhoneQCExporter;
use App\Filament\Resources\PhoneQCResource\Pages;
use App\Filament\Resources\PhoneQCResource\RelationManagers;
use App\Mail\AuditMail;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class PhoneQCResource extends Resource
{
    protected static ?string $model = PhoneQC::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $modelLabel = 'Phone QC';

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        $userTeams = $user->teams->pluck('slug')->toArray();

        // Hide the resource if the user is only in the SOS team
        if (in_array('sos-team', $userTeams) || in_array('cintas-ar-team', $userTeams)) {
            return false;
        }

        return true;
    }

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
                            ->required()
                            ->options(function (callable $get) {
                                $lob = $get('pqc_lob');
                                $query = User::whereHas('teams', function ($query) {
                                    $query->where('slug', 'truesource-team');
                                })
                                    ->whereNotIn('user_role', ['Manager', 'Admin']);

                                if ($lob) {
                                    $query->where(function ($query) use ($lob) {
                                        $query->whereJsonContains('user_lob', $lob)
                                            ->orWhere('user_lob', 'like', '%' . $lob . '%');
                                    });
                                }

                                return $query->pluck('name', 'id');
                            })
                            ->reactive()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('pqc_auditor')
                            ->label('Auditor')
                            ->default(fn () => Auth::user()->name)
                            ->readOnly(),
                        Forms\Components\TextInput::make('pqc_case_number')->label('Case/WO #'),
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
                        Forms\Components\FileUpload::make('pqc_call_recording')->label('Call Recording')
                            ->disk('public')
                            ->directory('recordings'),
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
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('sub_category', null);
                                        $set('pqc_weightage', null);
                                    })
                                    ->native(false),
                                Forms\Components\Select::make('sub_category')->label('Sub-Category')
                                    ->options(function (callable $get) {
                                        $category = $get('category');
                                        switch ($category) {
                                            case 'Opening and Closing Spiel':
                                                return [
                                                    'Name / Branding' => 'Name / Branding',
                                                    'Compliance - Recorded Line' => 'Compliance - Recorded Line',
                                                    'Thank you and Goodbye' => 'Thank you and Goodbye',
                                                ];
                                            case 'Customer Experience':
                                                return [
                                                    'Active Listening / Comprehension / Communication / Empathy' => 'Active Listening / Comprehension / Communication / Empathy',
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
                                            'Name / Branding' => 3,
                                            'Compliance - Recorded Line' => 5,
                                            'Thank you and Goodbye' => 2,
                                            'Active Listening / Comprehension / Communication / Empathy' => 10,
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
                                    ->readOnly(),
                                Forms\Components\TextInput::make('comments'),
                            ])->columns(4)
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
            ->recordUrl(false)
            ->headerActions([
                Tables\Actions\ExportAction::make()->label('Export Phone QCs')
                    ->exporter(PhoneQCExporter::class)
            ])
            ->columns([
                Tables\Columns\TextColumn::make('pqc_lob')->label('LOB')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pqc_auditor')->label('Auditor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pqc_audit_date')->label('Audit Date')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pqc_score')->label('Score')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pqc_status')->label('Status')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Disputed' => 'danger',
                        'Acknowledged' => 'success',
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('pqc_audit_date')
                    ->form([
                        Forms\Components\DatePicker::make('pqc_from'),
                        Forms\Components\DatePicker::make('pqc_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['pqc_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('pqc_audit_date', '>=', $date),
                            )
                            ->when(
                                $data['pqc_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('pqc_audit_date', '<=', $date),
                            );
                    })
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
                                ->required(),
                            Forms\Components\FileUpload::make('pqc_associate_screenshot')->label('Screenshot')
                                ->maxFiles(5)
                                ->multiple(),
                        ])
                        ->action(function (PhoneQC $record, array $data) {
                            $record->update([
                                'pqc_status' => 'Disputed',
                                'pqc_associate_feedback' => $data['pqc_associate_feedback'],
                                'pqc_associate_screenshot' => $data['pqc_associate_screenshot'],
                                'pqc_dispute_timestamp' => now(), // Add this line to set the dispute timestamp
                            ]);
                            $auditorRecipients = User::whereIn('user_role', ['Auditor', 'Manager'])
                            ->where('user_lob', $record->lob)
                            ->whereHas('teams', function ($query) {
                                $query->whereIn('teams.id', auth()->user()->teams->pluck('id'));
                            })
                            ->get();
                    
                                // Fetch all Auditors and Managers for the same lob & team
                                $auditorRecipients = User::whereIn('user_role', ['Auditor', 'Manager'])
                                ->whereJsonContains('user_lob', $record->pqc_lob)
                                ->whereHas('teams', function ($query) {
                                $query->whereIn('teams.id', auth()->user()->teams->pluck('id'));
                                })
                            ->get();

                                // Fetch the associate (audited person)
                                $auditedUser = User::find($record->user_id);
                                $user_name = $auditedUser->name;
                                $subject = "Phone QC Disputed: " . $record->id;
                                $body = "A Phone QC record by " . $user_name . " has been disputed. <br/><br/> Feedback: " . $data['pqc_associate_feedback'];

                                try {
                                    // Send emails to all auditors
                                    foreach ($auditorRecipients as $recipient) {
                                        try {
                                            Mail::to($recipient->email)
                                                ->send(new AuditMail($subject, $body));
                                            
                                            // Optional: Log successful email
                                            Log::info("Audit email sent successfully to auditor: {$recipient->email}");
                                        } catch (\Exception $e) {
                                            // Handle individual recipient failure
                                            Log::error("Failed to send audit email to {$recipient->email}: " . $e->getMessage());
                                            // Continue with other recipients instead of halting the entire process
                                        }
                                    }
                                
                                // Send email to the audited person
                                if ($auditedUser) {
                                    try {
                                    Mail::to($auditedUser->email)
                                    ->send(new AuditMail($subject, $body));
            
                                // Optional: Log successful email
                                    Log::info("Audit email sent successfully to audited user: {$auditedUser->email}");
                                        } catch (\Exception $e) {
                                // Log failure for the audited user
                                    Log::error("Failed to send audit email to audited user {$auditedUser->email}: " . $e->getMessage());
                                // You might want to handle this error differently
                                        }
                                    }
                                } catch (\Exception $e) {
                                // Handle any other unexpected errors
                                    Log::error("Fatal error in audit email process: " . $e->getMessage());
                                // You could rethrow the exception or return a response based on your needs
                                throw $e; // or return response()->json(['error' => 'Failed to send audit emails']);
                                }
                            })
                        ->requiresConfirmation()
                        ->visible(fn (PhoneQC $record) =>
                            in_array(Auth::user()->user_role, ['Auditor', 'Associate']) &&
                            $record->pqc_status === 'Pending' &&
                            (Auth::user()->user_role === 'Associate' ? Auth::id() === $record->user_id : true)
                        ),
                    Tables\Actions\Action::make('Acknowledge')
                        ->label('Acknowledge')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (PhoneQC $record) {
                            $record->update([
                                'pqc_status' => 'Acknowledged',
                                'pqc_acknowledge_timestamp' => now(),
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn (PhoneQC $record) =>
                            in_array(Auth::user()->user_role, ['Auditor', 'Associate']) &&
                            $record->pqc_status === 'Pending' &&
                            (Auth::user()->user_role === 'Associate' ? Auth::id() === $record->user_id : true)
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
                } elseif ($user->user_role !== 'Admin') {
                    // For non-Admin users, filter phone QCs based on their LOBs
                    $query->whereIn('pqc_lob', $user->user_lob);
                }
                // Admin users can see all phone QCs, so no additional filtering is needed for them
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
                                TextEntry::make('pqc_case_number')->label('Case/WO #'),
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
                                TextEntry::make('pqc_call_recording')
                                    ->label('Call Recording')
                                    ->formatStateUsing(function ($state) {
                                        if ($state) {
                                            $url = Storage::disk('public')->url($state);
                                            return new HtmlString("<a href='{$url}' download class='filament-button filament-button-size-sm inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset dark:focus:ring-offset-0 min-h-[2rem] px-3 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700'>Download Recording</a>");
                                        }
                                        return 'No recording available';
                                    }),
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
                            ]),
                        Tabs\Tab::make('Dispute Remarks')
                            ->schema([
                                TextEntry::make('pqc_associate_feedback')->label('Reason for Dispute'),
                                ImageEntry::make('pqc_associate_screenshot')->label('Screenshot'),
                                TextEntry::make('pqc_dispute_timestamp')->label('Dispute Filed On')
                                    ->dateTime('m/d/Y H:i:s'), // Add this line to display the dispute timestamp
                                TextEntry::make('pqc_acknowledge_timestamp')->label('Acknowledge Filed On')
                                    ->dateTime('m/d/Y H:i:s'),
                            ])->visible(fn ($record) => $record->pqc_status === 'Disputed'),
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
