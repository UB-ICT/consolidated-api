<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a navigational menu entry in the portal.
 *
 * Visibility is controlled by the role_menu pivot: no roles means
 * public to all authenticated users; otherwise the user must have
 * at least one of the attached roles.
 */
class Menu extends Model
{
    protected $connection = 'pgsql';

    use HasUuids;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_DISABLED = 'disabled';

    public const TYPE_APPLICATION = 'application';
    public const TYPE_SUBMENU = 'submenu';
    public const TYPE_USER_MENU = 'user-menu';
    public const TYPE_EXTERNAL_LINK = 'external-link';

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'label',
        'path',
        'icon',
        'type',
        'status',
        'description',
        'category',
        'parent_id',
        'sort_order',
    ];

    /**
     * Roles that can see this menu item.
     * Empty = visible to every authenticated user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menu', 'menu_id', 'role_id');
    }

    /**
     * Child menu items ordered for display.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Parent menu item this entry belongs to.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Visible when the menu has no role restrictions, or shares a role with the user.
     *
     * @param  array<int, string>  $roleIds
     */
    public function scopeVisibleToRoles(Builder $query, array $roleIds): Builder
    {
        return $query->where(function (Builder $visibility) use ($roleIds) {
            $visibility->whereDoesntHave('roles');

            if ($roleIds !== []) {
                $visibility->orWhereHas('roles', function (Builder $roles) use ($roleIds) {
                    $roles->whereIn('roles.id', $roleIds);
                });
            }
        });
    }
}
