<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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
        'aud_status',
        'aud_associate_feedback',
        'aud_associate_screenshot',
        'aud_dispute_timestamp'

    ];

    /**
     * Relationship between User and Audit
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
