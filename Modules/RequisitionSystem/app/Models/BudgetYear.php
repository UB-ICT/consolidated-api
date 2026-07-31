<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetYear extends Model
{
    protected $connection = 'porsql';

    protected $fillable = [
        'label',
        'submissions_open',
    ];

    protected $casts = [
        'submissions_open' => 'boolean',
    ];

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }
}
