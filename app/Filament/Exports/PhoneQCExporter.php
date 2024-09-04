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
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('user_id'),
            ExportColumn::make('pqc_lob'),
            ExportColumn::make('pqc_case_number'),
            ExportColumn::make('pqc_auditor'),
            ExportColumn::make('pqc_audit_date'),
            ExportColumn::make('pqc_date_processed'),
            ExportColumn::make('pqc_time_processed'),
            ExportColumn::make('pqc_type_of_call'),
            ExportColumn::make('pqc_call_summary'),
            ExportColumn::make('pqc_strengths'),
            ExportColumn::make('pqc_opportunities'),
            ExportColumn::make('pqc_call_recording'),
            ExportColumn::make('pqc_score'),
            ExportColumn::make('pqc_status'),
            ExportColumn::make('pqc_associate_feedback'),
            ExportColumn::make('pqc_dispute_timestamp'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
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
