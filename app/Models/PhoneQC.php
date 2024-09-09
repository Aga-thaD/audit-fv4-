<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneQC extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pqc_lob',
        'pqc_case_number',
        'pqc_auditor',
        'pqc_audit_date',
        'pqc_date_processed',
        'pqc_time_processed',
        'pqc_type_of_call',
        'pqc_call_summary',
        'pqc_strengths',
        'pqc_opportunities',
        'pqc_call_recording',
        'pqc_scorecard',
        'pqc_score',
        'pqc_status',
        'pqc_associate_feedback',
        'pqc_associate_screenshot',
        'pqc_dispute_timestamp',
    ];

    protected $casts = [
        'pqc_scorecard' => 'array'
    ];

    /**
     * Relationship between User and PhoneQC
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
