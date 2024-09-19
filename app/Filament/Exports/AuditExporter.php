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
            ExportColumn::make('user.name')
                ->label('Associate')
                ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
            ExportColumn::make('lob')->label('LOB'),
            ExportColumn::make('aud_auditor')->label('Auditor'),
            ExportColumn::make('aud_date')->label('Date'),
            ExportColumn::make('aud_date_processed')->label('Date Processed'),
            ExportColumn::make('aud_time_processed')->label('Time Processed'),
            ExportColumn::make('aud_case_number')->label('Case Number'),
            ExportColumn::make('aud_audit_type')->label('Audit Type'),
            ExportColumn::make('aud_customer')->label('Customer'),
            ExportColumn::make('aud_area_hit')->label('Area Hit'),
            ExportColumn::make('aud_error_category')->label('Error Category'),
            ExportColumn::make('aud_type_of_error')->label('Nature Of Error'),
            ExportColumn::make('aud_feedback')->label('Feedback')
                ->formatStateUsing(fn ($state) => strip_tags($state)),
            ExportColumn::make('aud_status')->label('Status'),
            ExportColumn::make('aud_associate_feedback')->label('Associate Feedback')
                ->formatStateUsing(fn ($state) => strip_tags($state)),
            ExportColumn::make('aud_dispute_timestamp')->label('Dispute Timestamp'),
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
