<?php

namespace App\Filament\Widgets;

use App\Models\PhoneQC;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPhoneQCs extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PhoneQC::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('pqc_lob')
                    ->label('LOB')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pqc_auditor')
                    ->label('Auditor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pqc_type_of_call')
                    ->label('Type of Call'),
                Tables\Columns\TextColumn::make('pqc_audit_date')
                    ->label('Audit Date')
                    ->date('m/d/Y'),
                Tables\Columns\TextColumn::make('pqc_score')
                    ->label('Score'),
                Tables\Columns\TextColumn::make('pqc_status')
                    ->label('Status'),
            ]);
    }
}
