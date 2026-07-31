<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    protected $connection = 'porsql';

    protected $fillable = [
        'parent_id',
        'account_no',
        'description',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('account_no');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function budgetLineItems(): HasMany
    {
        return $this->hasMany(BudgetLineItem::class);
    }

    /**
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $queue = $this->children()->pluck('id')->all();

        while ($queue !== []) {
            $childId = array_shift($queue);
            $ids[] = (int) $childId;

            $grandchildren = self::query()
                ->where('parent_id', $childId)
                ->pluck('id')
                ->all();

            foreach ($grandchildren as $grandchildId) {
                $queue[] = $grandchildId;
            }
        }

        return $ids;
    }
}
