<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use LdapRecord\Laravel\Auth\LdapAuthenticatable;
use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
use LdapRecord\Laravel\Auth\HasLdapUser;


class User extends Authenticatable implements LdapAuthenticatable
{
    use HasFactory, Notifiable, HasApiTokens, AuthenticatesWithLdap;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone_no',
        'organization',
        'picture',
        'device_token',
        'user_status_id',
        'role_id',
        'menu_id',
        'sender_id'
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

    // Defined a relationship with UserCampus that a user is assign to only one campus.
    public function userCampus()
    {
        return $this->hasOne(UserCampus::class, 'user_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function userStatus()
    {
        return $this->belongsTo(UserStatus::class, 'user_status_id');
    }
}