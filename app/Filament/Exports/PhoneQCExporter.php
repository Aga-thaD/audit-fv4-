<?php

namespace App\Filament\Exports;

use App\Models\PhoneQC;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PhoneQCExporter extends Exporter
{
    protected static ?string $model = PhoneQC::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user_id')
                ->label('Associate')
                ->formatStateUsing(fn ($state) => $state->name ?? $state),
            ExportColumn::make('pqc_lob')->label('LOB'),
            ExportColumn::make('pqc_case_number')->label('Case Number'),
            ExportColumn::make('pqc_auditor')->label('Auditor'),
            ExportColumn::make('pqc_audit_date')->label('Date'),
            ExportColumn::make('pqc_date_processed')->label('Date Processed'),
            ExportColumn::make('pqc_time_processed')->label('Time Processed'),
            ExportColumn::make('pqc_type_of_call')->label('Type of Call'),
            ExportColumn::make('pqc_call_summary')->label('Call Summary'),
            ExportColumn::make('pqc_strengths')->label('Strengths'),
            ExportColumn::make('pqc_opportunities')->label('Opportunities'),
            ExportColumn::make('pqc_scorecard')->label('Scorecard')
                ->formatStateUsing(fn ($state) => static::formatJsonColumn($state)),
            ExportColumn::make('pqc_score')->label('Score'),
            ExportColumn::make('pqc_status')->label('Status'),
            ExportColumn::make('pqc_associate_feedback')->label('Associate Feedback'),
            ExportColumn::make('pqc_dispute_timestamp')->label('Dispute Timestamp'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    protected static function formatJsonColumn($state): string
    {
        if (is_string($state)) {
            $state = json_decode($state, true);
        }

        if (!is_array($state)) {
            return '';
        }

        $formatted = [];
        foreach ($state as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $formatted[] = "$key: $value";
        }

        return implode(' | ', $formatted);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your phone qc export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
