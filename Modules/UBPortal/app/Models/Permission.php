<?php

namespace Modules\UBPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Represents a granular permission action.
 *
 * Permissions are grouped by category and assigned
 * to roles through a pivot relationship.
 */
class Permission extends Model
{
    use HasUuids;

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = ['category', 'action_name'];

    /**
     * Roles that include this permission.
     *
     * Uses the role_permissions pivot table.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}

// You'll notice we used BelongsToMany for things like user_groups and 
//role_permissions. In your migration files, you don't need a model for those
// "pivot" tables. Laravel is smart enough to handle them behind the scenes as 
//long as the table names match the ones in your relationships.
