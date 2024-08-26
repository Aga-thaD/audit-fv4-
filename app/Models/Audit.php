<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lob',
        'aud_auditor',
        'aud_date',
        'aud_date_processed',
    ];

    /**
     * Relationship between User and Audit
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
