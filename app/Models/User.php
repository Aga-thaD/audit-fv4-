<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements HasTenants, HasAvatar
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'avatar',
        'name',
        'email',
        'password',
        'user_role',
        'user_lob',

        //Permissions
        'audit_create',
        'audit_view',
        'audit_update',
        'audit_delete',
        'pqc_create',
        'pqc_view',
        'pqc_update',
        'pqc_delete',
        'user_create',
        'user_view',
        'user_update',
        'user_delete',
    ];

    protected $casts = [
        'user_lob' => 'array'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->teams;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->teams()->whereKey($tenant)->exists();
    }

    public function team(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar ? Storage::url($this->avatar) : null;
    }

    public function deleteAvatar()
    {
        if ($this->avatar) {
            Storage::delete($this->avatar);
            $this->avatar = null;
            $this->save();
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            $user->teams()->detach();
            // Add any other relationships that need to be handled here
            $user->audits()->delete();
            $user->phoneQCs()->delete();
        });
    }

    public function audits()
    {
        return $this->hasMany(Audit::class);
    }

    public function phoneQCs()
    {
        return $this->hasMany(PhoneQC::class);
    }
}
