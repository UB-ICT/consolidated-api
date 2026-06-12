<?php

namespace Modules\RequisitionSystem\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;

class Logs extends Model
{
    // Specifying connection since you use 'porsql' for this module
    protected $connection = 'porsql';

    protected $fillable = [
        'requisition_id',
        'stage_id',
        'user_id',
        'comments',
    ];

    /**
     * Get the requisition associated with the log.
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class, 'requisition_id');
    }

    /**
     * Get the workflow stage associated with the log.
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'stage_id');
    }

    /**
     * Get the user who triggered the log action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
