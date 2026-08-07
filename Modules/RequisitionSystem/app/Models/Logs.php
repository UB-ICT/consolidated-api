<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Support\RequisitionLogAction;

class Logs extends Model
{
    protected $connection = 'porsql';

    protected $table = 'logs';

    protected $fillable = [
        'requisition_id',
        'user_id',
        'action',
        'summary',
        'comments',
        'file_name',
        'file_path',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class, 'requisition_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hasAttachment(): bool
    {
        return filled($this->file_path) && filled($this->file_name);
    }

    public function isDecision(): bool
    {
        return in_array($this->action, [
            RequisitionLogAction::APPROVED,
            RequisitionLogAction::REJECTED,
        ], true);
    }
}
