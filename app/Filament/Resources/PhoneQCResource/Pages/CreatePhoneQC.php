<?php

namespace App\Filament\Resources\PhoneQCResource\Pages;

use App\Filament\Resources\PhoneQCResource;
use App\Mail\AuditMail;
use App\Models\Audit;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CreatePhoneQC extends CreateRecord
{
    protected static string $resource = PhoneQCResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;


        if ($record->pqc_score < 100) {
            $auditor = User::find($record->pqc_auditor);
            $audited = User::find($record->user_id);

            if ($auditor && $audited) {
                $subject = "Phone QC Alert: Score Below 100";

                $title = "Hello,\n\n"
                         . "The phone QC score has dropped below 100.\n\n"
                         . "Auditor: {$auditor->name}\n"
                         . "Audited: {$audited->name}\n"
                         . "Score: {$record->pqc_score}\n\n"
                         . "Please review the QC entry for more details.";

                $body = "Hello,\n\n"
                         . "The phone QC score has dropped below 100.\n\n";

                        Mail::to($auditor)
                         ->send(new AuditMail($title, $body));
         
                        Mail::to($audited->email)
                         ->send(new AuditMail($title, $body));
            }
        }
    }
}
