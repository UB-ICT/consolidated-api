<?php

namespace Modules\UBPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Auth\Models\User;


class Group extends Model
{
    use HasUuids;

    protected $fillable = ['group_name', 'description'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_groups');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'group_roles');
    }
}
