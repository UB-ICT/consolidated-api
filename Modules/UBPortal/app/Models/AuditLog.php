<?php

namespace Modules\UBPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

/**
 * Represents an immutable audit trail entry.
 *
 * Each log stores who performed an action, who or what
 * was targeted, and the related application context.
 */
class AuditLog extends Model
{
    protected $connection = 'ubportal';

    use HasUuids;

    /**
     * Audit logs are immutable and only store created_at.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = ['actor_id', 'target_id', 'app_id', 'action', 'severity'];

    /**
     * User who performed the audited action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * User affected by the audited action.
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    /**
     * Application context where the event occurred.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'app_id');
    }
}
