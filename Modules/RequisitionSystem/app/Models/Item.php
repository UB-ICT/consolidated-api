<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{

    protected $connection = 'porsql';

    protected $fillable = [
        'quantity',
        'unit_cost',
        'total',
        'comments',
        'requisition_id',
        'chart_of_account_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected $appends = [
        'line_item_number',
        'description',
    ];

    protected $with = [
        'chartOfAccount',
    ];

    public function getLineItemNumberAttribute(): ?string
    {
        return $this->chartOfAccount?->account_no;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->chartOfAccount?->description;
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class, 'requisition_id');
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }
}
