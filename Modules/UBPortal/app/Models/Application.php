<?php

namespace Modules\UBPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Represents an application registered in the portal.
 *
 * Applications can be linked to roles to scope access
 * rules and permission assignments.
 */
class Application extends Model
{
    use HasUuids;

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = ['app_name', 'description'];

    /**
     * Roles associated with this application.
     *
     * Uses the role_applications pivot table.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_applications', 'app_id', 'role_id');
    }
}
