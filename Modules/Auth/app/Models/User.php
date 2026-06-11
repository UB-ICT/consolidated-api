<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasUuids, Notifiable, HasApiTokens;

    protected $connection = 'pgsql';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'guid',
        'domain',
        'password',
        'profile_picture',
        'status',
        'last_active',
        'device_token',
        'google_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_active' => 'datetime',
        'password' => 'hashed',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Check if the user has a specific role by its name/slug.
     */
    public function hasRole(string $roleName): bool
    {
        // Checks if any of the loaded roles match the requested name
        return $this->roles->contains('name', $roleName);
    }

    /**
     * Get the cost centers this user is allowed to manage based on their assigned stages.
     * (Derived from your Seeder mapping: User -> UserStage -> Stage -> Pipeline)
     */
    public function costCenters()
    {
        // If your user has a direct department_id column instead, swap this logic out.
        // This helper assumes a user manages cost centers attached to their approval assignments.
        return $this->hasMany(\Modules\RequisitionSystem\Models\UserStage::class, 'user_id');
    }
}
