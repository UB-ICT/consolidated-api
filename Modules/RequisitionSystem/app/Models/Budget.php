<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\RequisitionSystem\Support\BudgetWorkflow;

class Budget extends Model
{
    protected $connection = 'porsql';

    protected $fillable = [
        'cost_center_id',
        'budget_year_id',
        'pipeline_id',
        'status_id',
        'stage_id',
        'current_stage_sequence',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function budgetYear(): BelongsTo
    {
        return $this->belongsTo(BudgetYear::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(BudgetLineItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BudgetApproval::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BudgetLog::class);
    }

    /**
     * Budgets still in progress (Draft / Pending).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas(
            'status',
            fn (Builder $statusQuery) => $statusQuery->whereIn(
                'name',
                BudgetWorkflow::inFlightStatuses()
            )
        );
    }

    /**
     * Budgets currently in force for a cost center.
     */
    public function scopeOperational(Builder $query): Builder
    {
        return $query->whereHas(
            'status',
            fn (Builder $statusQuery) => $statusQuery->where('name', 'Active')
        );
    }
}
