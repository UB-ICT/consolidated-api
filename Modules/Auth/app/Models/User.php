<?php

namespace Modules\Auth\Models;

// use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
// use LdapRecord\Laravel\Auth\LdapAuthenticatable;
// use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
// use LdapRecord\Laravel\Auth\HasLdapUser;
use Spatie\Permission\Traits\HasRoles;
use Modules\PublicSafety\Models\Role;
use Modules\PublicSafety\Models\UserCampus;
use Modules\PublicSafety\Models\UserStatus;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $connection = 'pgsql';
    protected $fillable = [
        'name',
        'email',
        'password',
        'domain',
        'device_token',
        'role_id',
        'menu_id',
        'campus_id',
        'user_status_id',
        'profile_picture',
        'google_id',
        'email_verified_at',

    ];
    public $timestamps = false;



    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',

    ];


    // Defined a relationship with Role that  states a user belongs a single role.
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function userStatus()
    {
        return $this->belongsTo(UserStatus::class, 'user_status_id');
    }
}
