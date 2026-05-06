<?php

namespace Modules\UBPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

/**
 * Represents a user's request for application access.
 *
 * Each request links the requester, target application,
 * desired role, and current approval status.
 */
class AccessRequest extends Model
{
    use HasUuids;

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = ['requester_id', 'app_id', 'requested_role_id', 'status'];

    /**
     * User who submitted the access request.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Application the user is requesting access to.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'app_id');
    }

    /**
     * Role requested for the application.
     */
    public function requestedRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'requested_role_id');
    }
}
