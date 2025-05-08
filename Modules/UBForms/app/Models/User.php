<?php

namespace App\Models;


// use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Foundation\Auth\User as Authenticatable;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Eloquent\Model; 
use MongoDB\Laravel\Relations\HasMany;


use LdapRecord\Laravel\Auth\LdapAuthenticatable;
use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;

use Laravel\Sanctum\HasApiTokens;
use LdapRecord\Laravel\Auth\HasLdapUser;

/* This creates a user collection in mongo db */



class User extends Authenticatable implements LdapAuthenticatable
{
    protected $connection = 'mongodb';
    protected $collection = 'users'; // Specify the collection name if different from the default

    //use HasFactory, Notifiable;
    use Notifiable, AuthenticatesWithLdap, HasApiTokens;
    
    

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // 'guid', //added
        // 'domain', //added
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
}



