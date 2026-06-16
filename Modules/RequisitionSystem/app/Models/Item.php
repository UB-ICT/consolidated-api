<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['description', 'quantity', 'line_item_number', 'unit_cost', 'total', 'comments', 'requisition_id'];

    /**
     * Get the parent requisition that owns this line item.
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class, 'requisition_id');
    }
}
