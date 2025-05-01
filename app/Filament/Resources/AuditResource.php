<?php

namespace App\Filament\Resources;

use App\Filament\Exports\AuditExporter;
use App\Filament\Resources\AuditResource\Pages;
use App\Filament\Resources\AuditResource\RelationManagers;
use App\Mail\AuditMail;
use App\Models\Audit;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Exports\Exporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Table;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('lob')
                ->required()
                ->label('LOB')
                ->columnSpanFull()
                ->options(function () {
                    $user = Auth::user();
                    $isSOSTeam = $user->teams->contains('slug', 'sos-team');
                    $isTrueSourceTeam = $user->teams->contains('slug', 'truesource-team');
                    $isCintasARTeam = $user->teams->contains('slug', 'cintas-ar-team');

                    $options = [];

                    if ($user->user_role === 'Admin') {
                        $options = [
                            'CALL ENTERING' => 'CALL ENTERING',
                            'ERG FOLLOW-UP' => 'ERG FOLLOW-UP',
                            'DOCUMENT PROCESSING' => 'DOCUMENT PROCESSING',
                            'CUSTOMER SERVICE REP' => 'CUSTOMER SERVICE REP',
                            'ACCOUNTS RECEIVABLE/PAYABLE' => 'ACCOUNTS RECEIVABLE/PAYABLE',
                            'CINTAS ACCOUNTS RECEIVABLE' => 'CINTAS ACCOUNTS RECEIVABLE',
                        ];
                    } elseif ($isSOSTeam && $isTrueSourceTeam) {
                        $options = [
                            'CALL ENTERING' => 'CALL ENTERING',
                            'ERG FOLLOW-UP' => 'ERG FOLLOW-UP',
                            'DOCUMENT PROCESSING' => 'DOCUMENT PROCESSING',
                            'CUSTOMER SERVICE REP' => 'CUSTOMER SERVICE REP',
                            'ACCOUNTS RECEIVABLE/PAYABLE' => 'ACCOUNTS RECEIVABLE/PAYABLE',
                        ];
                    } elseif ($isSOSTeam) {
                        $options = [
                            'CUSTOMER SERVICE REP' => 'CUSTOMER SERVICE REP',
                            'ACCOUNTS RECEIVABLE/PAYABLE' => 'ACCOUNTS RECEIVABLE/PAYABLE',
                        ];
                    } elseif ($isTrueSourceTeam) {
                        $options = [
                            'CALL ENTERING' => 'CALL ENTERING',
                            'ERG FOLLOW-UP' => 'ERG FOLLOW-UP',
                            'DOCUMENT PROCESSING' => 'DOCUMENT PROCESSING',
                        ];
                    } elseif ($isCintasARTeam) {
                        $options = [
                            'CINTAS ACCOUNTS RECEIVABLE' => 'CINTAS ACCOUNTS RECEIVABLE',
                        ];
                    }

                    return $options;
                })
                ->reactive()
                ->afterStateUpdated(function (callable $set) {
                    $set('user_id', null);
                    $set('aud_error_category', null);
                    $set('aud_error_type', null);
                })
                ->required(),
                Forms\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                            
                                Forms\Components\Select::make('user_id')->label('Name')
                                    ->required()
                                    ->options(function (callable $get) {
                                        $lob = $get('lob');
                                        $query = User::query()
                                            ->whereNotIn('user_role', ['Admin', 'Manager']);

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
                                    ->preload()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $user_info = User::find($state);
                                        $set('eo_number', $user_info->eo_number);
                                    }),
                                Forms\Components\TextInput::make('aud_auditor')
                                    ->required()
                                    ->label('Auditor')
                                    ->default(fn () => Auth::user()->name)
                                    ->readOnly(),
                                Forms\Components\DatePicker::make('aud_date')
                                    ->required()
                                    ->label('Audit Date')
                                    ->default(fn () => Carbon::today()->toDateString())
                                    ->format('Y-m-d')
                                    ->displayFormat('m/d/Y')
                                    ->readOnly(),
                                // Cintas AR specific fields
                                Forms\Components\TextInput::make('eo_number')
                                    ->label('EO Number')
                                    ->required()
                                    ->visible(fn (callable $get) => $get('lob') === 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\DatePicker::make('invoice_date')
                                    ->label('Invoice Date')
                                    ->required()
                                    ->format('Y-m-d')
                                    ->displayFormat('m/d/Y')
                                    ->visible(fn (callable $get) => $get('lob') === 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\TextInput::make('document_number')
                                    ->label('Document Number')
                                    ->required()
                                    ->visible(fn (callable $get) => $get('lob') === 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\Select::make('country')
                                    ->label('Country')
                                    ->options([
                                        'CAN' => 'CAN', 'USA' => 'USA'])
                                    ->required()
                                    ->visible(fn (callable $get) => $get('lob') === 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Amount')
                                    ->required()
                                    ->numeric()
                                    ->visible(fn (callable $get) => $get('lob') === 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\TextInput::make('reference')
                                    ->label('Reference')
                                    ->required()
                                    ->visible(fn (callable $get) => $get('lob') === 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\Select::make('pass_fail')
                                    ->label('Pass/Fail')
                                    ->options([
                                        'Pass' => 'Pass',
                                        'Fail' => 'Fail',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->visible(fn (callable $get) => $get('lob') === 'CINTAS ACCOUNTS RECEIVABLE'),
                                // Standard fields for non-Cintas teams
                                Forms\Components\DatePicker::make('aud_date_processed')->label('Date Processed')
                                    ->required()
                                    ->format('Y-m-d')
                                    ->displayFormat('m/d/Y')
                                    ->visible(fn (callable $get) => $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\Select::make('aud_time_processed')->label('Time Processed')
                                    ->required()
                                    ->options([
                                        'Prime' => 'Prime',
                                        'Afterhours' => 'Afterhours',
                                    ])
                                    ->native(false)
                                    ->visible(fn (callable $get) => $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\TextInput::make('aud_case_number')->label('Case/WO #')
                                    ->visible(fn () => !Auth::user()->teams->contains('slug', 'sos-team') &&
                                        !Auth::user()->teams->contains('slug', 'cintas-ar-team'))
                                    ->required(fn (callable $get) => $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\Select::make('aud_audit_type')->label('Type of Audit')
                                    ->required()
                                    ->options([
                                        'Internal' => 'Internal',
                                        'Client' => 'Client',
                                    ])
                                    ->native(false)
                                    ->visible(fn (callable $get) => $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\TextInput::make('aud_customer')->label('Customer')
                                    ->required()
                                    ->visible(fn (callable $get) => $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE'),
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
                                    ->native(false)
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state === 'Not Applicable') {
                                            $set('aud_error_category', 'NOT APPLICABLE');
                                            $set('aud_type_of_error', null);
                                        } else {
                                            $set('aud_error_category', null);
                                            $set('aud_type_of_error', null);
                                        }
                                    })
                                    ->visible(fn (callable $get) => $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\Select::make('aud_error_category')
                                    ->label('Error Category')
                                    ->options([
                                        'CRITICAL' => 'CRITICAL',
                                        'MAJOR' => 'MAJOR',
                                        'MINOR' => 'MINOR',
                                        'NOT APPLICABLE' => 'NOT APPLICABLE',
                                    ])
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, callable $get) {
                                        $set('aud_type_of_error', null);

                                        // Clear the error type if it's not in the new category's options
                                        $lob = $get('lob');
                                        $category = $get('aud_error_category');
                                        $currentErrorType = $get('aud_type_of_error');

                                        if ($lob && $category && $category !== 'NOT APPLICABLE') {
                                            $validOptions = self::getErrorTypes()[$lob][$category] ?? [];
                                            if (!in_array($currentErrorType, array_keys($validOptions))) {
                                                $set('aud_type_of_error', null);
                                            }
                                        }
                                    })
                                    ->required(fn (callable $get) => $get('aud_area_hit') !== 'Not Applicable' && $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE')
                                    ->disabled(fn (callable $get) => $get('aud_area_hit') === 'Not Applicable')
                                    ->dehydrated()
                                    ->visible(fn (callable $get) => $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\Select::make('aud_type_of_error')
                                    ->label('Error Type')
                                    ->options(function (callable $get) {
                                        $lob = $get('lob');
                                        $category = $get('aud_error_category');
                                        if (!$lob || !$category || $category === 'NOT APPLICABLE') {
                                            return [];
                                        }
                                        return self::getErrorTypes()[$lob][$category] ?? [];
                                    })
                                    ->reactive()
                                    ->searchable()
                                    ->required(fn (callable $get) => $get('aud_area_hit') !== 'Not Applicable' && $get('aud_error_category') !== 'NOT APPLICABLE' && $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE')
                                    ->disabled(fn (callable $get) => $get('aud_area_hit') === 'Not Applicable' || $get('aud_error_category') === 'NOT APPLICABLE')
                                    ->dehydrated()
                                    ->visible(fn (callable $get) => $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                // Cintas specific error fields
                                Forms\Components\Select::make('type_of_error')
                                    ->label('Type of Error')
                                    ->options([
                                        'MAJOR' => 'MAJOR',
                                        'MINOR' => 'MINOR',
                                    ])
                                    ->reactive()
                                    ->required()
                                    ->visible(fn (callable $get) => $get('lob') === 'CINTAS ACCOUNTS RECEIVABLE'),
                                Forms\Components\Select::make('description_of_error')
                                    ->label('Description of Error')
                                    ->options(function (callable $get) {
                                        $lob = $get('lob');
                                        $category = $get('type_of_error');
                                        if (!$lob || !$category || $lob !== 'CINTAS ACCOUNTS RECEIVABLE') {
                                            return [];
                                        }
                                        return self::getErrorTypes()[$lob][$category] ?? [];
                                    })
                                    ->reactive()
                                    ->searchable()
                                    ->required()
                                    ->visible(fn (callable $get) => $get('lob') === 'CINTAS ACCOUNTS RECEIVABLE'),
                            ]),
                        Forms\Components\Select::make('aud_source_type')
                            ->label('Source Type')
                            ->options([
                                'System Integration' => 'System Integration',
                                'Manual' => 'Manual',
                            ])
                            ->required()
                            ->visible(function (callable $get) {
                                return $get('lob') === 'CALL ENTERING';
                            })
                            ->reactive()
                            ->native(false),
                        Forms\Components\RichEditor::make('aud_feedback')->label('Feedback')
                            ->required()
                            ->visible(function (callable $get) {
                                $user = Auth::user();
                                return ($user->teams->contains('slug', 'truesource-team') || $user->user_role === 'Admin') && $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE';
                            }),
                        Forms\Components\FileUpload::make('aud_screenshot')->label('Screenshot')
                            ->multiple()
                            ->maxFiles(5)
                            ->visible(function (callable $get) {
                                $user = Auth::user();
                                return ($user->teams->contains('slug', 'truesource-team') || $user->user_role === 'Admin') && $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE';
                            }),
                        Forms\Components\RichEditor::make('aud_fascilit_notes')->label('FascilIT Notes')
                            ->required()
                            ->visible(function (callable $get) {
                                $user = Auth::user();
                                return ($user->teams->contains('slug', 'sos-team') || $user->user_role === 'Admin') && $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE';
                            }),
                        Forms\Components\FileUpload::make('aud_attachmment')->label('Attachment')
                            ->visible(function (callable $get) {
                                $user = Auth::user();
                                return ($user->teams->contains('slug', 'sos-team') || $user->user_role === 'Admin') && $get('lob') !== 'CINTAS ACCOUNTS RECEIVABLE';
                            }),
                        // Cintas AR comments field
                        Forms\Components\Textarea::make('comments')
                            ->label('Comments')
                            ->required()
                            ->visible(fn (callable $get) => $get('lob') === 'CINTAS ACCOUNTS RECEIVABLE'),
                        Forms\Components\Hidden::make('aud_status')
                            ->default('Pending'),
                    ])->visible(function(Get $get) {
                        if($get('lob') !== null) {
                            return true;
                        }
                        else {
                            return false;//
                        }
                    })
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(false)
            ->headerActions([
                Tables\Actions\ExportAction::make()->label('Export Audits')
                    ->exporter(AuditExporter::class)
            ])
            ->columns([
                Tables\Columns\TextColumn::make('lob')->label('LOB')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('aud_auditor')->label('Auditor')
                    ->searchable(),
                // Conditional columns based on team
                Tables\Columns\TextColumn::make('aud_case_number')->label('Case/WO #')
                    ->visible(fn () => !Auth::user()->teams->contains('slug', 'sos-team') &&
                        !Auth::user()->teams->contains('slug', 'cintas-ar-team'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('eo_number')->label('EO Number')
                    ->visible(fn () => Auth::user()->teams->contains('slug', 'cintas-ar-team'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_number')->label('Document Number')
                    ->visible(fn () => Auth::user()->teams->contains('slug', 'cintas-ar-team'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('aud_error_category')->label('Error Category')
                    ->visible(fn () => !Auth::user()->teams->contains('slug', 'cintas-ar-team'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type_of_error')->label('Type of Error')
                    ->visible(fn () => Auth::user()->teams->contains('slug', 'cintas-ar-team'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('aud_date')->label('Audit Date')
                    ->searchable(),
                Tables\Columns\TextColumn::make('aud_customer')->label('Customer')
                    ->visible(fn () => !Auth::user()->teams->contains('slug', 'cintas-ar-team'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('pass_fail')->label('Pass/Fail')
                    ->visible(fn () => Auth::user()->teams->contains('slug', 'cintas-ar-team'))
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pass' => 'success',
                        'Fail' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('aud_status')->label('Status')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Disputed' => 'danger',
                        'Acknowledged' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('aud_date')
                    ->form([
                        Forms\Components\DatePicker::make('aud_from'),
                        Forms\Components\DatePicker::make('aud_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['aud_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('aud_date', '>=', $date),
                            )
                            ->when(
                                $data['aud_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('aud_date', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->visible(fn (Model $record) =>
                        static::canEdit($record)
                        )
                        ->before(function (Model $record) {
                            if (!static::canEdit($record)) {
                                abort(403, 'You are not authorized to edit this audit.');
                            }
                        }),
                    // Update the Dispute action
Tables\Actions\Action::make('Dispute')
->label('Dispute')
->icon('heroicon-o-exclamation-circle')
->color('warning')
->form([
    Forms\Components\Textarea::make('aud_associate_feedback')->label('Reason for Dispute')
        ->required(),
    Forms\Components\FileUpload::make('aud_associate_screenshot')->label('Screenshot')
        ->maxFiles(5)
        ->multiple(),
])
->action(function (Audit $record, array $data) {
    // Get current history or initialize empty array
    $history = $record->event_history ?? [];
    
    // Create new history entry
    $entry = [
        'user_id' => Auth::id(),
        'user_name' => Auth::user()->name,
        'user_role' => Auth::user()->user_role,
        'action_type' => 'dispute',
        'message' => $data['aud_associate_feedback'],
        'old_status' => $record->aud_status,
        'new_status' => 'Disputed',
        'attachments' => $data['aud_associate_screenshot'] ?? [],
        'timestamp' => now(),
    ];
    
    // Add entry to history
    $history[] = $entry;
    
    $record->update([
        'aud_status' => 'Disputed',
        'aud_associate_feedback' => $data['aud_associate_feedback'],
        'aud_associate_screenshot' => $data['aud_associate_screenshot'],
        'aud_dispute_timestamp' => now(),
        'event_history' => $history
    ]);

                                    // Fetch all Auditors and Managers for the same lob & team
        $auditorRecipients = User::whereIn('user_role', ['Auditor', 'Manager'])
        ->whereJsonContains('user_lob', $record->lob)
        ->whereHas('teams', function ($query) {
            $query->whereIn('teams.id', auth()->user()->teams->pluck('id'));
        })
        ->get();

            // Fetch all Auditors and Managers for the same lob & team
            $auditorRecipients = User::whereIn('user_role', ['Auditor', 'Manager'])
            ->whereJsonContains('user_lob', $record->lob)
            ->whereHas('teams', function ($query) {
            $query->whereIn('teams.id', auth()->user()->teams->pluck('id'));
            })
        ->get();

            // Fetch the associate (audited person)
            $auditedUser = User::find($record->user_id);
            $user_name = $auditedUser->name;
            $subject = "Audit Disputed: " . $record->id;
            $body = "An audit by " . $user_name . " has been disputed.<br/><br/>Feedback: " . $data['aud_associate_feedback'];

            try {
                // Send email to auditors and managers
                foreach ($auditorRecipients as $recipient) {
                    try {
                        Mail::to($recipient->email)
                            ->send(new AuditMail($subject, $body));
                        
                        // Optional: Log successful email
                        Log::info("Audit email sent successfully to recipient: {$recipient->email}");
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
                        ->visible(fn (Audit $record) =>
                            in_array(Auth::user()->user_role, ['Auditor', 'Associate']) &&
                            $record->aud_status === 'Pending' &&
                            (Auth::user()->user_role === 'Associate' ? Auth::id() === $record->user_id : true)
                        ),
                        Tables\Actions\Action::make('Acknowledge')
                        ->label('Acknowledge')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Audit $record) {
                            try {
                                // Get current history or initialize empty array
                                $history = $record->event_history ?? [];
                                
                                // Create new history entry
                                $entry = [
                                    'user_id' => Auth::id(),
                                    'user_name' => Auth::user()->name,
                                    'user_role' => Auth::user()->user_role,
                                    'action_type' => 'acknowledge',
                                    'message' => 'I acknowledge this audit finding.',
                                    'old_status' => $record->aud_status,
                                    'new_status' => 'Acknowledged',
                                    'attachments' => [],
                                    'timestamp' => now(),
                                ];
                                
                                // Add entry to history
                                $history[] = $entry;
                                // Check if the auditor belongs to the Cintas team
                                $belongsToCintas = auth()->user()->teams()->where('name', 'Cintas')->exists();
                    
                                if (!$belongsToCintas) {
                                    Log::info("Acknowledgement skipped - auditor not in Cintas team.");
                                } else {
                                    $recipients = User::whereIn('user_role', ['Auditor', 'Manager'])
                                        ->whereJsonContains('user_lob', $record->lob)
                                        ->whereHas('teams', function ($query) {
                                            $query->where('teams.name', 'Cintas');
                                        })
                                        ->get();
                    
                                    $user_name = User::find($record->user_id)->name;
                                    $body = "Audit by " . $user_name . " has been acknowledged.";
                                    $title = "Audit Acknowledgement";
                    
                                    foreach ($recipients as $recipient) {
                                        try {
                                            Mail::to($recipient)
                                                ->send(new AuditMail($title, $body));
                    
                                            Log::info("Audit acknowledgement email sent successfully to: {$recipient->email}");
                                        } catch (\Exception $e) {
                                            Log::error("Failed to send audit acknowledgement email to {$recipient->email}: " . $e->getMessage());
                                        }
                                    }
                                }
                    
                                $record->update([
                                    'aud_status' => 'Acknowledged',
                                    'aud_acknowledge_timestamp' => now(),
                                    'event_history' => $history
                                ]);
                    
                                Log::info("Audit record {$record->id} marked as acknowledged");
                            } catch (\Exception $e) {
                                Log::error("Fatal error in audit acknowledgement process: " . $e->getMessage());
                                throw $e;
                            }
                        })                    
                        ->requiresConfirmation()
                        ->visible(function (Audit $record) {
                            if($record->aud_status === 'Pending')
                            {
                                if(Auth::user()->id === $record->user_id)
                                {
                                    return true;
                                }
                                else
                                {
                                    return false;
                                }
                            }
                        }),
                    
                    Tables\Actions\Action::make('Reply')
                    ->label('Reply')
                    ->icon('heroicon-o-inbox')
                    ->color('info')
                    ->visible(function (Audit $record) {
                        $user = auth()->user();
                        $isTrueSourceTeam = $user->teams->contains('slug', 'truesource-team');
                        return $isTrueSourceTeam && $record->aud_status === 'Disputed';
                    })
                    ->form([
                        Forms\Components\Textarea::make('reply_message')
                        ->label('Your Reply')
                        ->required()
                        ->rows(10),
                    ])
                        ->action(function (Audit $record, array $data) {
                            try {
                                $currentUser = auth()->user();
                                $replyMessage = $data['reply_message'];

                             /*   $history = $record->event_history ?? [];
            
                            // Create new history entry
                            $entry = [
                                'user_id' => Auth::id(),
                                'user_name' => Auth::user()->name,
                                'user_role' => Auth::user()->user_role,
                                'action_type' => 'reply',
                                'message' => $replyMessage,
                                'attachments' => $data['attachments'] ?? [],
                                'timestamp' => now(),
                            ];
            
                            // Add entry to history
                            $history[] = $entry;
            
                             // Update the audit with the new history
                            $record->update([
                                'event_history' => $history
                            ]); */
                                
                                // Get the auditor user
                                $auditor = User::where('name', $record->aud_auditor)->first();
                                
                                // Get the auditee user
                                $auditee = User::find($record->user_id);
                                
                                if (!$auditor || !$auditee) {
                                    Log::error("Could not find auditor or auditee for audit ID: {$record->id}");
                                    return;
                                }
                                
                                // Determine if current user is the auditor or auditee
                                $isAuditor = ($currentUser->name === $record->aud_auditor);

                                // Set recipient based on who's replying
                                $recipient = $isAuditor ? $auditee : $auditor;
                                
                                $title = "Reply to Audit Dispute: {$record->id}";
                                $body = "<strong>Reply from: {$currentUser->name}</strong><br/><br/>" .
                                        "<strong>Original Audit:</strong> {$record->lob} - {$record->aud_date}<br/>" .
                                        "<strong>Message:</strong><br/>{$replyMessage}";
                                
                                // Send email
                                Mail::to($recipient->email)
                                    ->send(new AuditMail($title, $body));
                                
                                // Log successful email
                                Log::info("Audit reply email sent from {$currentUser->name} to {$recipient->email}");
                                
                            } catch (\Exception $e) {
                                Log::error("Failed to send audit reply email: " . $e->getMessage());
                            }
                        })
                        ->modalHeading('Reply Form')
                        ->modalSubmitActionLabel('Send Reply')
                        ->requiresConfirmation(),

                        Tables\Actions\Action::make('Mark as Pending')
                        ->label('Mark as Pending')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->action(function (Audit $record) {
                            // Get current history or initialize empty array
                            $history = $record->event_history ?? [];
                            
                            // Create new history entry
                            $entry = [
                                'user_id' => Auth::id(),
                                'user_name' => Auth::user()->name,
                                'user_role' => Auth::user()->user_role,
                                'action_type' => 'status_change',
                                'message' => 'Changed status from Disputed to Pending',
                                'old_status' => $record->aud_status,
                                'new_status' => 'Pending',
                                'attachments' => [],
                                'timestamp' => now(),
                            ];
                            
                            // Add entry to history
                            $history[] = $entry;
                            
                            $record->update([
                                'aud_status' => 'Pending',
                                'event_history' => $history
                            ]);
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
                    ExportAction::make(),
                ])->hidden(function () {
                    $user = auth()->user();

                    if ($user->user_role === 'Associate' && $user->teams->contains('slug', 'truesource-team')) {
                        return true;
                    }

                    return false;
                }),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if ($user->user_role === 'Associate') {
                    $query->where('user_id', $user->id);
                } elseif ($user->user_role !== 'Admin') {
                    // For non-Admin users, filter audits based on their LOBs
                    $query->whereIn('lob', $user->user_lob);
                }
                // Admin users can see all audits, so no additional filtering is needed for them
            });
    }

    public static function canEdit(Model $record): bool
    {
        if (!$record instanceof Audit) {
            return false;
        }

        $user = Auth::user();

        switch ($user->user_role) {
            case 'Admin':
            case 'Manager':
                return true;
            case 'Auditor':
                // Auditor can edit all audits except those where they are the subject
                return $record->user_id !== $user->id;
            case 'Associate':
            default:
                return false;
        }
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
                                                // Standard fields for non-Cintas teams
                                                TextEntry::make('aud_date_processed')->label('Date Processed')->date('m/d/Y')
                                                    ->visible(fn ($record) => $record->lob !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('aud_time_processed')->label('Time Processed')
                                                    ->visible(fn ($record) => $record->lob !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('aud_case_number')->label('Case/WO #')
                                                    ->visible(fn ($record) => $record->lob !== 'CINTAS ACCOUNTS RECEIVABLE' &&
                                                        !Auth::user()->teams->contains('slug', 'sos-team')),
                                                TextEntry::make('aud_audit_type')->label('Type of Audit')
                                                    ->visible(fn ($record) => $record->lob !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('aud_customer')->label('Customer')
                                                    ->visible(fn ($record) => $record->lob !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('aud_area_hit')->label('Area Hit')
                                                    ->visible(fn ($record) => $record->lob !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('aud_error_category')->label('Error Category')
                                                    ->visible(fn ($record) => $record->lob !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('aud_type_of_error')->label('Error Type')
                                                    ->visible(fn ($record) => $record->lob !== 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('aud_source_type')->label('Source Type')
                                                    ->visible(fn ($record) => $record->lob === 'CALL ENTERING'),
                                                // Cintas AR specific fields
                                                TextEntry::make('eo_number')->label('EO Number')
                                                    ->visible(fn ($record) => $record->lob === 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('ar_name')->label('AR Name')
                                                    ->visible(fn ($record) => $record->lob === 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('invoice_date')->label('Invoice Date')->date('m/d/Y')
                                                    ->visible(fn ($record) => $record->lob === 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('document_number')->label('Document Number')
                                                    ->visible(fn ($record) => $record->lob === 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('country')->label('Country')
                                                    ->visible(fn ($record) => $record->lob === 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('amount')->label('Amount')
                                                    ->visible(fn ($record) => $record->lob === 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('reference')->label('Reference')
                                                    ->visible(fn ($record) => $record->lob === 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('pass_fail')->label('Pass/Fail')
                                                    ->visible(fn ($record) => $record->lob === 'CINTAS ACCOUNTS RECEIVABLE')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'Pass' => 'success',
                                                        'Fail' => 'danger',
                                                        default => 'gray',
                                                    }),
                                                TextEntry::make('type_of_error')->label('Type of Error')
                                                    ->visible(fn ($record) => $record->lob === 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('description_of_error')->label('Description of Error')
                                                    ->visible(fn ($record) => $record->lob === 'CINTAS ACCOUNTS RECEIVABLE'),
                                                TextEntry::make('comments')->label('Comments')
                                                    ->visible(fn ($record) => $record->lob === 'CINTAS ACCOUNTS RECEIVABLE'),
                                            ]),
                                    ]),
                                Section::make()
                                    ->schema([
                                        TextEntry::make('aud_feedback')->label('Feedback')->html()
                                            ->visible(fn ($record) => ($record->lob !== 'CINTAS ACCOUNTS RECEIVABLE') &&
                                                (Auth::user()->teams->contains('slug', 'truesource-team') || Auth::user()->user_role === 'Admin')),
                                        ImageEntry::make('aud_screenshot')->label('Screenshot')
                                            ->visible(fn ($record) => ($record->lob !== 'CINTAS ACCOUNTS RECEIVABLE') &&
                                                (Auth::user()->teams->contains('slug', 'truesource-team') || Auth::user()->user_role === 'Admin')),
                                        TextEntry::make('aud_fascilit_notes')->label('FascilIT Notes')->html()
                                            ->visible(fn ($record) => ($record->lob !== 'CINTAS ACCOUNTS RECEIVABLE') &&
                                                (Auth::user()->teams->contains('slug', 'sos-team') || Auth::user()->user_role === 'Admin')),
                                        ImageEntry::make('aud_attachmment')->label('Attachment')
                                            ->visible(fn ($record) => ($record->lob !== 'CINTAS ACCOUNTS RECEIVABLE') &&
                                                (Auth::user()->teams->contains('slug', 'sos-team') || Auth::user()->user_role === 'Admin')),
                                        TextEntry::make('aud_status')->label('Status'),
                                    ])
                            ]),
                            Tabs\Tab::make('Dispute Remarks')
                            ->schema([
                                TextEntry::make('aud_associate_feedback')->label('Reason for Dispute'),
                                ImageEntry::make('aud_associate_screenshot')->label('Screenshot'),
                                TextEntry::make('aud_dispute_timestamp')->label('Dispute Filed On')
                                    ->timezone('America/New_York')
                                    ->dateTime('m/d/Y'),
                                TextEntry::make('aud_acknowledge_timestamp')->label('Acknowledge Filed On')
                                    ->timezone('America/New_York')
                                    ->dateTime('m/d/Y'),
                            ])->visible(fn ($record) => $record->aud_status === 'Disputed'),
                        Tabs\Tab::make('Event History')
                            ->schema([
                                RepeatableEntry::make('event_history')
                                    ->hiddenLabel()
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('timestamp')
                                                    ->label('Date & Time')
                                                    ->dateTime('m/d/Y')
                                                    ->timezone('America/New_York')
                                                    ->columnSpan(1),
                                                TextEntry::make('user_name')
                                                    ->label('User')
                                                    ->columnSpan(1),
                                                TextEntry::make('action_type')
                                                    ->label('Action')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'dispute' => 'danger',
                                                        'reply' => 'info',
                                                        'acknowledge' => 'success',
                                                        'status_change' => 'warning',
                                                        default => 'gray',
                                                    })
                                                    ->columnSpan(1),
                                            ]),
                                        TextEntry::make('message')
                                            ->label('Message')
                                            ->columnSpanFull()
                                            ->html(),
                                        TextEntry::make('old_status')
                                            ->label('Previous Status')
                                            ->visible(fn ($state) => !empty($state))
                                            ->badge()
                                            ->color('gray'),
                                        TextEntry::make('new_status')
                                            ->label('New Status')
                                            ->visible(fn ($state) => !empty($state))
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'Pending' => 'warning',
                                                'Disputed' => 'danger',
                                                'Acknowledged' => 'success',
                                                default => 'gray',
                                            }),
                                        ImageEntry::make('attachments')
                                            ->label('Attachments')
                                            ->visible(fn ($state) => !empty($state))
                                            ->columnSpanFull(),
                                    ])
                                    ->contained(false),
                            ])->visible(fn ($record) => !empty($record->event_history)),
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
                    'Security or dafety box/recall' => 'Security or dafety box/recall',
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
                    'Documents not split' => 'Documents not split',
                    'Reason code' => 'Reason code',
                ],
                'MINOR' => [
                    'Invalid transfer' => 'Invalid transfer',
                    'Incorrect document label' => 'Incorrect document label',
                    'Duplicate attachment' => 'Duplicate attachment',
                ],
            ],
            'CUSTOMER SERVICE REP' => [
                'CRITICAL' => [
                    'Incorrect location - dispatch' => 'Incorrect location - dispatch',
                    'Rudeness' => 'Rudeness',
                    'Back charge/Unapproved Work' => 'Back charge/Unapproved Work',
                ],
                'MAJOR' => [
                    'Email not sent to AH support' => 'Email not sent to AH support',
                    'Incorrect account status' => 'Incorrect account status',
                    'Incorrect NTE' => 'Incorrect NTE',
                    'Incorrect Trade - Dispatch' => 'Incorrect Trade - Dispatch',
                    'PO not accepted – Portal (IVR)' => 'PO not accepted – Portal (IVR)',
                    'Missed check in/out – (IVR)' => 'Missed check in/out – (IVR)',
                    'Missing/incorrect Portal notes' => 'Missing/incorrect Portal notes',
                    'Missed to email vendor' => 'Missed to email vendor',
                    'Missed to email Account team' => 'Missed to email Account team',
                    'Incomplete Follow up' => 'Incomplete Follow up',
                    'Missed to upload documents' => 'Missed to upload documents',
                    'Missed to follow special instructions' => 'Missed to follow special instructions',
                ],
                'MINOR' => [
                    'Incomplete Portal details' => 'Incomplete Portal details',
                    'Incorrect ETA date' => 'Incorrect ETA date',
                    'Missing/Incorrect work order notes' => 'Missing/Incorrect work order notes',
                    'Duplicate attachment' => 'Duplicate attachment',
                ],
            ],
            'ACCOUNTS RECEIVABLE/PAYABLE' => [
                'CRITICAL' => [
                    'Incorrect Unit Price' => 'Incorrect Unit Price',
                    'Rudeness' => 'Rudeness',
                ],
                'MAJOR' => [
                    'Incorrect document attached' => 'Incorrect document attached',
                    'Missed to upload paperwork' => 'Missed to upload paperwork',
                    'Missing Email' => 'Missing Email',
                    'Missed/Incorrect address' => 'Missed/Incorrect address',
                    'Incorrect invoice number' => 'Incorrect invoice number',
                    'Incorrect invoice date' => 'Incorrect invoice date',
                    'Incorrect Type info' => 'Incorrect Type info',
                ],
                'MINOR' => [
                    'Duplicate attachment' => 'Duplicate attachment',
                ],
            ],
            'CINTAS ACCOUNTS RECEIVABLE' => [
                'MAJOR' => [
                    'Failed to follow R&R' => 'Failed to follow R&R',
                    'Did not follow Minimum - Stop Charge' => 'Did not follow Minimum - Stop Charge',
                    'Incorrect Adjustment - did not match the R&R' => 'Incorrect Adjustment - did not match the R&R',
                    'Missing or Incorrect Details on Text Reference Key' => 'Missing or Incorrect Details on Text Reference Key',
                    'Failed to Include Tax' => 'Failed to Include Tax',
                    'Failed to follow correct MLA Pricing' => 'Failed to follow correct MLA Pricing',
                    'Exceeded QTY Restrictions' => 'Exceeded QTY Restrictions',
                    'Incorrect Total' => 'Incorrect Total',
                    'Incorrect Subtotal' => 'Incorrect Subtotal',
                    'Incorrect Date' => 'Incorrect Date',
                    'Incorrect INV Number' => 'Incorrect INV Number',
                    'Incorrect Tax Code' => 'Incorrect Tax Code',
                    'Incorrect item quantities in VA01' => 'Incorrect item quantities in VA01',
                    'Incorrect Short Pay' => 'Incorrect Short Pay',
                    'Incorrect Tax Amount' => 'Incorrect Tax Amount',
                    'Industrial Management (Incorrect %)' => 'Industrial Management (Incorrect %)',
                    'Invoice doesn\'t match VA01' => 'Invoice doesn\'t match VA01',
                    'Overpaid Invoice' => 'Overpaid Invoice',
                    'No "REF KEY 3" keyed in FB60' => 'No "REF KEY 3" keyed in FB60',
                    'No data entered in Reference column' => 'No data entered in Reference column',
                    'Incorrect Service Charge' => 'Incorrect Service Charge',
                    'Paid to the wrong vendor' => 'Paid to the wrong vendor',
                    'Incorrect Surcharge Amount' => 'Incorrect Surcharge Amount',
                    'Missing Employees' => 'Missing Employees',
                    'Tax Not Included' => 'Tax Not Included',
                    'Incorrect Document Number in Completed Invoice Copy' => 'Incorrect Document Number in Completed Invoice Copy',
                    'Others' => 'Others',
                    
                ],
                'MINOR' => [
                    'Failed to follow R&R' => 'Failed to follow R&R',
                    'Did not follow Minimum - Stop Charge' => 'Did not follow Minimum - Stop Charge',
                    'Incorrect Adjustment - did not match the R&R' => 'Incorrect Adjustment - did not match the R&R',
                    'Missing or Incorrect Details on Text Reference Key' => 'Missing or Incorrect Details on Text Reference Key',
                    'Failed to Include Tax' => 'Failed to Include Tax',
                    'Failed to follow correct MLA Pricing' => 'Failed to follow correct MLA Pricing',
                    'Exceeded QTY Restrictions' => 'Exceeded QTY Restrictions',
                    'Incorrect Total' => 'Incorrect Total',
                    'Incorrect Subtotal' => 'Incorrect Subtotal',
                    'Incorrect Date' => 'Incorrect Date',
                    'Incorrect INV Number' => 'Incorrect INV Number',
                    'Incorrect Tax Code' => 'Incorrect Tax Code',
                    'Incorrect item quantities in VA01' => 'Incorrect item quantities in VA01',
                    'Incorrect Short Pay' => 'Incorrect Short Pay',
                    'Incorrect Tax Amount' => 'Incorrect Tax Amount',
                    'Industrial Management (Incorrect %)' => 'Industrial Management (Incorrect %)',
                    'Invoice doesn\'t match VA01' => 'Invoice doesn\'t match VA01',
                    'Overpaid Invoice' => 'Overpaid Invoice',
                    'No "REF KEY 3" keyed in FB60' => 'No "REF KEY 3" keyed in FB60',
                    'No data entered in Reference column' => 'No data entered in Reference column',
                    'Incorrect Service Charge' => 'Incorrect Service Charge',
                    'Paid to the wrong vendor' => 'Paid to the wrong vendor',
                    'Incorrect Surcharge Amount' => 'Incorrect Surcharge Amount',
                    'Missing Employees' => 'Missing Employees',
                    'Tax Not Included' => 'Tax Not Included',
                    'Incorrect Document Number in Completed Invoice Copy' => 'Incorrect Document Number in Completed Invoice Copy',
                    'Others' => 'Others',
                ],
            ],
        ];
    }
}
