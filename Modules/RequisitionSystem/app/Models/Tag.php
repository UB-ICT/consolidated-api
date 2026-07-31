<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $connection = 'porsql';

    protected $fillable = [
        'name',
        'cost_center_id',
    ];

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function requisitions(): BelongsToMany
    {
        return $this->belongsToMany(
            Requisition::class,
            'requisition_tag',
            'tag_id',
            'requisition_id'
        )->withTimestamps();
    }
}
