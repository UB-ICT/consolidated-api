<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCostCenter extends Model
{
    protected $connection = 'porsql';

    protected $table = 'user_cost_center';

    protected $fillable = [
        'user_id',
        'cost_center_id',
    ];

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
