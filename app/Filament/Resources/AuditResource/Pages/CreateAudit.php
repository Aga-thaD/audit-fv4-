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
              
                $body = "Below are the audit details." . "<br><br><br>" . "<strong>LOB</strong> - " . $this->record->lob .  " <br><br> " . "<strong>Name: </strong>" . $ar_email->name . "<br><br>" . 
                        "<strong>Auditor: </strong>" . $this->record->aud_auditor . "<br><br>" . "<strong>Audit Date: </strong>" . $this->record->aud_date . 
                        "<br><br>" . "<strong>EO Number: </strong>" . $this->record->eo_number . "<br><br>" . "<strong>Reference: </strong>" . $this->record->reference . 
                        "<br><br>" . "<strong>Pass/Fail: </strong> " . $this->record->pass_fail . "<br><br>" . "<strong>Type of Error: </strong>" . 
                        $this->record->type_of_error . "<br><br>" . "<strong>Description of Error: </strong>" . $this->record->description_of_error . 
                        "<br><br><br>" . "<strong>Status: </strong>" . $this->record->aud_status;
                        
                $title = "Audit Details";

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

