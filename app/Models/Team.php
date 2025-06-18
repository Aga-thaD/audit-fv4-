<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function phoneqcs(): HasMany
    {
        return $this->hasMany(PhoneQC::class);
    }

    // Rename from members() to users()
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    // You can keep the members() method for backward compatibility if needed
    public function members(): BelongsToMany
    {
        return $this->users();
    }
}
