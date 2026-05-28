<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Auth\Models\User;

/**
 * Represents a user group for access organization.
 *
 * Groups connect users to one or more roles,
 * enabling shared permission management.
 */
class Group extends Model
{
    protected $connection = 'pgsql';

    use HasUuids;

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = ['group_name', 'description'];

    /**
     * Users assigned to this group.
     *
     * Uses the user_groups pivot table.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_groups');
    }

    /**
     * Roles associated with this group.
     *
     * Uses the group_roles pivot table.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'group_roles');
    }
}
