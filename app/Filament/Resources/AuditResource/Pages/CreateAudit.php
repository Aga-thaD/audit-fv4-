<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use App\Mail\AuditMail;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateAudit extends CreateRecord
{
    protected static string $resource = AuditResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {

        if($this->record->lob === "CINTAS ACCOUNTS RECEIVABLE") { 
            if($this->record->pass_fail === "Fail") 
            {
                
                $manager_email = auth()->user()->email;
                $ar_info = $this->record->user_id;
                $ar_email = User::find($ar_info);

                $body = "Audit with EO Number - " . $this->record->eo_number . " by " . $ar_email->name . "'s is tagged as FAILED.";
                $title = "Audit Fail - " . $this->record->eo_number;

            Mail::to($manager_email)
                ->send(new AuditMail($title, $body));

            Mail::to($ar_email->email)
                ->send(new AuditMail($title, $body));
            }
        }
   

    return Notification::make()
            ->title('Audit Created!')
            ->success()
            ->send();

    }

}

