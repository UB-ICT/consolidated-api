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

    protected static function booted(): void
    {
        // line_item_number and description always mirror the linked chart of
        // account so they can never drift from valid, centrally-managed values.
        static::saving(function (Item $item) {
            if (!$item->isDirty('chart_of_account_id')) {
                return;
            }

            $chartOfAccount = $item->chartOfAccount()->first();

            $item->line_item_number = $chartOfAccount?->account_no;
            $item->description = $chartOfAccount?->description;
        });
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
