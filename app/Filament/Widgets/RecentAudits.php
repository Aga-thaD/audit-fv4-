<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAudits extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Audit::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('aud_case_number')
                    ->label('Case/WO #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lob')
                    ->label('LOB')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('aud_auditor')
                    ->label('Auditor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('aud_error_category')
                    ->label('Error Category'),
                Tables\Columns\TextColumn::make('aud_type_of_error')
                    ->label('Error Type'),
                Tables\Columns\TextColumn::make('aud_date')
                    ->label('Audit Date')
                    ->date(),
                Tables\Columns\TextColumn::make('aud_status')
                    ->label('Status'),
            ]);
    }
}
