<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'lob',
        'aud_auditor',
        'aud_date',
        'aud_date_processed',
        'aud_time_processed',
        'aud_case_number',
        'aud_audit_type',
        'aud_customer',
        'aud_area_hit',
        'aud_error_category',
        'aud_type_of_error',
        'aud_source_type',
        'aud_feedback',
        'aud_screenshot',
        'aud_fascilit_notes',
        'aud_attachmment',
        'aud_status',
        'aud_associate_feedback',
        'aud_associate_screenshot',
        'aud_dispute_timestamp',
        'aud_acknowledge_timestamp',
        'event_history',

        // Cintas AR specific fields
        'eo_number',
        'ar_name',
        'invoice_date',
        'document_number',
        'country',
        'amount',
        'reference',
        'pass_fail',
        'type_of_error',
        'description_of_error',
        'comments',
    ];

    /**
     * Relationship between User and Audit
     */

    protected $casts = [
        'event_history' => 'array',
        'aud_date' => 'date',
        'aud_date_processed' => 'date',
        'invoice_date' => 'date',
        'aud_dispute_timestamp' => 'datetime',
        'aud_acknowledge_timestamp' => 'datetime',
        'aud_screenshot' => 'array', // Add this line to cast the field as an array
    ];

    public function addHistoryEntry(string $actionType, string $message, string $reply, string $reason, ?string $oldStatus = null, ?string $newStatus = null, array $attachments = []): bool
{
    try {
        // Get current history or initialize empty array
        $history = $this->event_history ?? [];

        // Create new history entry
        $entry = [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'user_role' => auth()->user()->user_role,
            'action_type' => $actionType,
            'message' => $message,
            'reply' => $reply,
            'reason' => $reason,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'attachments' => $attachments,
            'timestamp' => now(),
        ];

        // Add entry to history
        $history[] = $entry;

        // Update the audit
        return $this->update([
            'event_history' => $history
        ]);
    } catch (\Exception $e) {
        Log::error("Failed to add history entry to audit #{$this->id}: " . $e->getMessage());
        return false;
    }
}

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
