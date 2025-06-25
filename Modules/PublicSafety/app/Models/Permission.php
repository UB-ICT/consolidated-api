<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\PublicSafety\Database\Factories\PermissionFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];

    public static function defaultPermissions()
    {
        return [
            'create-user',
            'edit-user',
            'delete-user',
            'view-user',
            'create-role',
            'edit-role',
            'delete-role',
            'view-role',
            'create-permission',
            'edit-permission',
            'delete-permission',
            'view-permission',
        ];
    }
}
