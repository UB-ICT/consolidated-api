<?php

namespace Modules\UBPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents an access-control role.
 *
 * Roles group permissions and can be associated with
 * applications and menu items.
 */
class Role extends Model
{
    protected $connection = 'ubportal';

    use HasUuids;

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

    /**
     * Applications this role applies to.
     *
     * 'app_id' is specified explicitly because the column name
     * does not follow Laravel's default convention (application_id).
     */
    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'role_applications', 'role_id', 'app_id');
    }

    /**
     * Menu items directly associated with this role.
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }
}
