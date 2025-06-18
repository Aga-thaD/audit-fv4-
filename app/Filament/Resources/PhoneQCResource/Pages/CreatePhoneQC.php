<?php

namespace App\Filament\Resources\PhoneQCResource\Pages;

use App\Filament\Resources\PhoneQCResource;
use App\Mail\AuditMail;
use App\Models\Audit;
use App\Models\User;
use Filament\Notifications\Notification;
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
        try {
            $record = $this->record;
            $score = $record->pqc_score;
        
            if ($score < 100) {
                $auditor = auth()->user();
                $auditee = \App\Models\User::find($record->user_id);
        
                if ($auditor && $auditee) {
                    $title = "Phone QC Alert: Score Below 100";
        
                    $body = "A Phone QC has been submitted and recorded with a markdown.<br/><br/>" .
                            "<strong>Auditee:</strong> {$auditee->name}<br/>" .
                            "<strong>Auditor:</strong> {$auditor->name}<br/>" .
                            "<strong>Score:</strong> {$score}<br/>";
        
                    try {
                        Mail::to([$auditor->email, $auditee->email])
                            ->send(new AuditMail($title, $body));
                        
                        Log::info("Phone QC alert email sent successfully to auditor ({$auditor->email}) and auditee ({$auditee->email})");
                    } catch (\Exception $e) {
                        Log::error("Failed to send Phone QC alert email: " . $e->getMessage());
                        // Optionally show a notification that email failed but record was created
                        // Notification::make()->title('Record created but email notification failed')->warning()->send();
                    }
                } else {
                    Log::warning("Could not send Phone QC alert email: Auditor or auditee not found");
                }
            }
        } catch (\Exception $e) {
            // Handle any unexpected errors in the afterCreate process
            Log::error("Fatal error in Phone QC afterCreate process: " . $e->getMessage());
            // The record is already created at this point, so we just log the error
        }
    }
    
    // Optional: still show toast notification for user feedback
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Phone QC Created')
            ->success();
    }
}