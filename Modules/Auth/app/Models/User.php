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
     * Check if the user has a specific role by its exact name.
     */
    public function hasRole(string $roleName): bool
    {
        // Safe check: If roles aren't eager loaded yet, query using 'role_name'
        if (!$this->relationLoaded('roles')) {
            return $this->roles()->where('role_name', $roleName)->exists(); // Fix here
        }

        // If they are loaded, scan the collection using 'role_name'
        return $this->roles->contains('role_name', $roleName); // Fix here
    }
    /**
     * Get the cost centers assigned to this user.
     */
    public function costCenters(): BelongsToMany
    {
        // Point it to your CostCenter model, passing the pivot table name
        return $this->belongsToMany(
            \Modules\RequisitionSystem\Models\CostCenter::class,
            'user_cost_center',
            'user_id',
            'cost_center_id'
        )->withTimestamps();
    }
}
