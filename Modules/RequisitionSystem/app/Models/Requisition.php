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
        'purchase_order_number',
        'purchase_order_file_name',
        'purchase_order_file_path',
        'purchase_order_emailed_at',
        'cost_center_id',
        'pipeline_id',
        'status_id',
        'currency_id',
        'total',
        'discount_type',
        'discount_value',
        'discount_amount',
        'priority',
        'description',
        'expected_delivery_date',
        'stage_id',
        'date_prepared',
        'is_recurring',
        'requires_downpayment',
        'quote_waiver_reason',
        'reminder_date',
        'current_stage_sequence',
    ];

    protected $casts = [
        'date_prepared' => 'datetime:M d, Y',
        'expected_delivery_date' => 'date:Y-m-d',
        'reminder_date' => 'date:Y-m-d',
        'purchase_order_emailed_at' => 'datetime',
        'is_recurring' => 'boolean',
        'requires_downpayment' => 'boolean',
        'total' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    protected $hidden = [
        'purchase_order_file_path',
    ];

    protected $appends = [
        'has_purchase_order_file',
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

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'stage_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'requisition_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Logs::class, 'requisition_id');
    }

    public function updateLogs(): HasMany
    {
        return $this->hasMany(RequisitionUpdateLog::class, 'requisition_id');
    }


    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'requisition_id');
    }


    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'requisition_tag',
            'requisition_id',
            'tag_id'
        )->withTimestamps()->orderBy('tags.name');
    }

    public function getHasPurchaseOrderFileAttribute(): bool
    {
        return filled($this->purchase_order_file_path);
    }

    public function preferredSupplier(): ?Supplier
    {
        $this->loadMissing('suppliers');

        $recommended = $this->suppliers->first(
            fn (Supplier $supplier) => (bool) ($supplier->pivot?->is_recommended)
        );

        return $recommended ?? $this->suppliers->first();
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
