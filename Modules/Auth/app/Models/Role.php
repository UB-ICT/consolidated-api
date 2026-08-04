<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Represents an access-control role.
 *
 * Roles group permissions and can be associated with
 * applications and menu items.
 */
class Role extends Model
{
    use HasUuids;

    protected $connection = 'pgsql';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = ['role_name', 'description'];

    /**
     * Permissions assigned to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_roles',
            'role_id', // Foreign key on user_roles matching this Role
            'user_id'  // Foreign key on user_roles matching User
        );
    }

    /**
     * Applications this role applies to.
     *
     * 'app_id' is specified explicitly because the column name
     * does not follow Laravel's default convention (application_id).
     */
    // public function applications(): BelongsToMany
    // {
    //     return $this->belongsToMany(Application::class, 'role_applications', 'role_id', 'app_id');
    // }

    /**
     * Menu items associated with this role via role_menu.
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'role_menu', 'role_id', 'menu_id');
    }

    /**
     * @deprecated Use menus() — kept for transitional callers.
     */
    public function menuItems(): BelongsToMany
    {
        return $this->menus();
    }
}
