<?php

namespace App\Filament\Exports;

use App\Models\Audit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AuditExporter extends Exporter
{
    protected static ?string $model = Audit::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('user_id'),
            ExportColumn::make('lob'),
            ExportColumn::make('aud_auditor'),
            ExportColumn::make('aud_date'),
            ExportColumn::make('aud_date_processed'),
            ExportColumn::make('aud_time_processed'),
            ExportColumn::make('aud_case_number'),
            ExportColumn::make('aud_audit_type'),
            ExportColumn::make('aud_customer'),
            ExportColumn::make('aud_area_hit'),
            ExportColumn::make('aud_error_category'),
            ExportColumn::make('aud_nature_of_error'),
            ExportColumn::make('aud_feedback'),
            ExportColumn::make('aud_status'),
            ExportColumn::make('aud_associate_feedback'),
            ExportColumn::make('aud_dispute_timestamp'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your audit export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
