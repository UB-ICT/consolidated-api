<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

class RequisitionUpdateLog extends Model
{
    protected $connection = 'porsql';

    protected $table = 'requisition_update_logs';

    protected $fillable = [
        'requisition_id',
        'user_id',
        'submitted',
        'event',
        'summary',
        'changes',
        'activity_comment',
    ];

    protected $casts = [
        'submitted' => 'boolean',
        'changes'   => 'array',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class, 'requisition_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
