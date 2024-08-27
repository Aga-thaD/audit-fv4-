<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneQC extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pqc_lob'
    ];

    /**
     * Relationship between User and PhoneQC
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
