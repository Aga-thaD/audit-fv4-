<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use App\Mail\AuditMail;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CreateAudit extends CreateRecord
{
    protected static string $resource = AuditResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        try {
            //sends email for audits with specific LOBS and error types
            $lob = strtolower($this->record->lob);
            $error = strtolower($this->record->aud_error_category ??'');
    
            //sets lobs limitation
            $validLobs = ['call entering', 'erg follow-up', 'document processing'];
            $validErrors = ['critical', 'major', 'minor'];
    
            if (in_array($lob, $validLobs)&&in_array($error, $validErrors)) {
                $auditorEmail=auth()->user()->email;
                $auditeeId=$this->record->user_id;
                $auditee=\App\Models\User::find($auditeeId);
    
                $title = "Audit Error Category";
    
                $body = "An audit has been submitted and recorded. <br/><br/>".
                "<strong>LOB:<strong/>{$this->record->lob}<br/>". 
                "<strong>Error Category:<strong/>{$this->record->aud_error_category}<br/>". 
                "<strong>Audited User:<strong/>{$auditee->name}<br/>".
                "<strong>Created By:<strong/>". auth()->user()->name;
    
                try {
                    Mail::to([$auditorEmail, $auditee->email])
                        ->send(new AuditMail($title, $body));
                    
                    Log::info("Audit notification emails sent successfully to auditor and auditee");
                } catch (\Exception $e) {
                    Log::error("Failed to send audit notification emails: " . $e->getMessage());
                    // Optionally show a notification to the user that email failed
                    // Notification::make()->title('Failed to send email notification')->danger()->send();
                }
            }
    
            if($this->record->lob === "CINTAS ACCOUNTS RECEIVABLE") { 
                if($this->record->pass_fail === "Fail") 
                {
                    
                    $manager_email = auth()->user()->email;
                    $ar_info = $this->record->user_id;
                    $ar_email = User::find($ar_info);
                  
                    $body = "Below are the audit details." . "<br><br><br>" . "<strong>LOB</strong> - " . $this->record->lob .  " <br><br> " . "<strong>Name: </strong>" . 
                            $ar_email->name . "<br><br>" . "<strong>Auditor: </strong>" . $this->record->aud_auditor . "<br><br>" . "<strong>Audit Date: </strong>" . 
                            $this->record->aud_date . "<br><br>" . "<strong>EO Number: </strong>" . $this->record->eo_number . "<br><br>" . "<strong>Reference: </strong>" . 
                            $this->record->reference . "<br><br>" . "<strong>Pass/Fail: </strong> " . $this->record->pass_fail . "<br><br>" . "<strong>Type of Error: </strong>" . 
                            $this->record->type_of_error . "<br><br>" . "<strong>Description of Error: </strong>" . $this->record->description_of_error . 
                            "<br><br><br>" . "<strong>Status: </strong>" . $this->record->aud_status;
                            
                    $title = "Audit Details";
    
                    // Send to manager
                    try {
                        Mail::to($manager_email)
                            ->send(new AuditMail($title, $body));
                        
                        Log::info("Audit details email sent successfully to manager: {$manager_email}");
                    } catch (\Exception $e) {
                        Log::error("Failed to send audit details email to manager {$manager_email}: " . $e->getMessage());
                    }
    
                    // Send to AR email
                    try {
                        Mail::to($ar_email->email)
                            ->send(new AuditMail($title, $body));
                        
                        Log::info("Audit details email sent successfully to AR: {$ar_email->email}");
                    } catch (\Exception $e) {
                        Log::error("Failed to send audit details email to AR {$ar_email->email}: " . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            // Handle any other unexpected errors in the entire notification process
            Log::error("Fatal error in audit notification process: " . $e->getMessage());
            
            // Optionally show a notification to the user
            // Notification::make()->title('Error processing audit notification')->danger()->send();
        }
   
        return Notification::make()
                ->title('Audit Created!')
                ->success()
                ->send();
    }
}