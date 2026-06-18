<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

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
        'priority',
        'expected_delivery_date',
        'stage_id',
        'date_prepared',
        'is_recurring',
        'reminder_date',
        'expiration_date',
    ];

    protected $casts = [
        'date_prepared' => 'datetime:M d, Y',
        'expected_delivery_date' => 'date:Y-m-d',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $requisition) {
            if (!$requisition->date_prepared) {
                $requisition->date_prepared = now();
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'requisition_id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(
            Supplier::class,
            'requisition_suppliers',
            'requisition_id',
            'supplier_id'
        )
            ->withPivot('is_recommended', 'quoted_total', 'quote_reference_number')
            ->withTimestamps();
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'stage_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Scope a query to only include upcoming scheduled or expiring recurring requisitions.
     */
    public function scopeScopeUpcomingReminders(Builder $query, int $daysAhead = 30): Builder
    {
        return $query->where('is_recurring', true)
            ->whereBetween(
                'reminder_date',
                [now()->toDateString(), now()->addDays($daysAhead)->toDateString()]
            );
    }
}
