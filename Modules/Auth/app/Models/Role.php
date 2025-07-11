<?php

//This Role models is to differnet the roles for the Admin portal and Anonymous app.
//Which are the Super Admins who have acess to everything and standard admin who view reports.
//The others are the Employees and students who will be using the anonymous app

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;


class Role extends SpatieRole
{
    use HasFactory;
     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];
    protected $connection = 'pgsql';
    public $timestamps = false;

    public static function defaultRoles()
    {
        return [
            'super-admin',
            'admin',
            'staff',
        ];
    }

    //Defined a relationship with User Model which states that a role can be associated with many users
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function accessRights()
    {
        return $this->hasMany(AccessRight::class);
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_roles');
    }

    public function menuRoles()
    {
        return $this->hasOne(MenuRole::class, 'role_id');
    }
}