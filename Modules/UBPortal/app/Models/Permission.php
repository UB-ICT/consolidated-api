<?php

namespace Modules\UBPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasUuids;

    protected $fillable = ['category', 'action_name'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}

// You'll notice we used BelongsToMany for things like user_groups and 
//role_permissions. In your migration files, you don't need a model for those
// "pivot" tables. Laravel is smart enough to handle them behind the scenes as 
//long as the table names match the ones in your relationships.
