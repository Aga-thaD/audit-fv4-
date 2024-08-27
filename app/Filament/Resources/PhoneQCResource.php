<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhoneQCResource\Pages;
use App\Filament\Resources\PhoneQCResource\RelationManagers;
use App\Models\PhoneQC;
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

class PhoneQCResource extends Resource
{
    protected static ?string $model = PhoneQC::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Phone QC';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Details')
                    ->schema([
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
                            ->format('m-d-Y')
                            ->displayFormat('m-d-Y')
                            ->readOnly(),
                        Forms\Components\DatePicker::make('pqc_date_processed')->label('Date Processed'),
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
                                                    'Name (3)' => 'Name (3)',
                                                    'Branding (3)' => 'Branding (3)',
                                                    'Compliance - Recorded Line (5)' => 'Compliance - Recorded Line (5)',
                                                    'Thank you and Goodbye (2)' => 'Thank you and Goodbye (2)',
                                                ];
                                            case 'Customer Experience':
                                                return [
                                                    'Active Listening / Comprehension / Communication (10)' => 'Active Listening / Comprehension / Communication (10)',
                                                    'Empathy' => 'Empathy (10)',
                                                    'Professionalism' => 'Professionalism (20)',
                                                ];
                                            case 'Procedural Adherence':
                                                return [
                                                    'Proper Probing (20)' => 'Proper Probing (20)',
                                                    'Process Mastery (20)' => 'Process Mastery (20)',
                                                    'Sense of Urgency (10)' => 'Sense of Urgency (10)',
                                                    'Accuracy (10)' => 'Accuracy (10)',
                                                ];
                                            default:
                                                return [];
                                        }
                                    })
                                    ->reactive()
                                    ->searchable(),
                            ])->addActionLabel('Add CTQ')->columns(2)
                    ]),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListPhoneQCS::route('/'),
            'create' => Pages\CreatePhoneQC::route('/create'),
            'edit' => Pages\EditPhoneQC::route('/{record}/edit'),
        ];
    }
}
