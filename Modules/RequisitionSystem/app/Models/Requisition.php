<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Requisition extends Model
{
    protected $connection = 'porsql';

    protected $fillable = [
        'number',
        'cost_center_id',
        'status_id',
        'currency_id',
        'conversion_rate_id',
        'total',
        'stage_id',
        'date_prepared'
    ];

    /**
     * 🔥 FIX: Add the missing attribute casts expected by your Unit Test
     */
    protected $casts = [
        'date_prepared' => 'datetime:M d, Y',
    ];

    /**
     * Define the relationship to line items
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'requisition_id');
    }

    /**
     * Define the relationship to your multi-vendor sourcing matrix
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'requisition_suppliers', 'requisition_id', 'supplier_id')
            ->withPivot('is_recommended', 'quoted_total', 'quote_reference_number')
            ->withTimestamps();
    }
}
