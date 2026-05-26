<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use LdapRecord\Laravel\Auth\LdapAuthenticatable;
use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
use LdapRecord\Laravel\Auth\HasLdapUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\UBPortal\Models\Group;
use Modules\UBPortal\Models\Role;



class User extends Authenticatable implements MustVerifyEmail, LdapAuthenticatable
{
    use HasFactory, HasUuids, Notifiable, HasApiTokens, HasRoles, AuthenticatesWithLdap, HasLdapUser;

    protected $connection = 'pgsql';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'domain',
        'device_token',
        'role_id',
        'menu_id',
        'user_status_id',
        'profile_picture',
        'google_id',
        'email_verified_at',
        'cost_center_id',
    ];

    public $timestamps = false;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'user_groups');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }
}
