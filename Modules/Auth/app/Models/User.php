<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\RequisitionSystem\Models\Supplier;
use Modules\RequisitionSystem\Models\CostCenter;

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

    /**
     * The roles belonging to the user.
     */
    public function roles(): BelongsToMany
    {
        // Using 'user_roles' table as defined in your setup
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    /**
     * The cost centers assigned to the user.
     */
    public function costCenters(): BelongsToMany
    {
        // If your user_cost_center table resides on a different connection (like 'porsql'),
        // we append the connection name directly to the table parameter string for isolation safety.
        return $this->belongsToMany(CostCenter::class, 'user_cost_center', 'user_id', 'cost_center_id')
            ->withTimestamps();
    }

    /**
     * Suppliers approved by this user.
     */
    public function suppliers(): HasOne
    {
        return $this->hasOne(Supplier::class, 'approved_by_user_id');
    }
}
