<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

class BudgetLog extends Model
{
    protected $connection = 'porsql';

    protected $table = 'budget_logs';

    protected $fillable = [
        'budget_id',
        'user_id',
        'action',
        'summary',
        'comments',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
