<?php

namespace Modules\UBPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a navigational menu entry in the portal.
 *
 * Menu items can be tied to roles and organized into
 * parent/child hierarchies for nested navigation.
 */
class MenuItem extends Model
{
    use HasUuids;

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'label',
        'path',
        'icon',
        'role_id',
        'parent_id',
        'sort_order'
    ];

    /**
     * Role that controls visibility/access for this menu item.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Child menu items ordered for display.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }
}
