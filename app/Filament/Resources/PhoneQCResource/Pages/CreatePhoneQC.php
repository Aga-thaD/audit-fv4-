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
                    \Illuminate\Support\Facades\Mail::to([$auditor->email, $auditee->email])
                        ->send(new \App\Mail\AuditMail($title, $body));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to send QC email: " . $e->getMessage());
                }
            }
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