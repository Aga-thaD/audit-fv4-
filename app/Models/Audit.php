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
    ];

    /**
     * Relationship between User and Audit
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
