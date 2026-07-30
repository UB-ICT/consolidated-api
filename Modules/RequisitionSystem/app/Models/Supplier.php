<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\RequisitionSystem\Support\SupplierStatus;

class Supplier extends Model
{
    protected $connection = 'porsql';

    protected $fillable = [
        'name',
        'contact_person',
        'phone_number',
        'email',
        'TAX',
        'status_id',
        'notes',
        'approved_by_user_id',
    ];

    public function scopePending(Builder $query): Builder
    {
        $pendingStatusId = Status::where('name', SupplierStatus::PENDING)->value('id')
            ?? SupplierStatus::PENDING_ID;

        return $query->where('status_id', $pendingStatusId);
    }

    public function scopeSelectable(Builder $query): Builder
    {
        $excludedStatusIds = Status::query()
            ->whereIn('name', [SupplierStatus::DELETED, SupplierStatus::REJECTED])
            ->pluck('id')
            ->filter()
            ->all();

        if ($excludedStatusIds === []) {
            return $query;
        }

        return $query->whereNotIn('status_id', $excludedStatusIds);
    }

    public function isRejected(): bool
    {
        return strcasecmp($this->status?->name ?? '', SupplierStatus::REJECTED) === 0;
    }

    public function isSelectable(): bool
    {
        return !$this->isDeleted() && !$this->isRejected();
    }

    public function isDeleted(): bool
    {
        return strcasecmp($this->status?->name ?? '', SupplierStatus::DELETED) === 0;
    }

    /**
     * Get the banking details for the supplier.
     */
    public function bankAccount(): HasOne
    {
        return $this->hasOne(SupplierBank::class, 'supplier_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function requisitions(): BelongsToMany
    {
        return $this->belongsToMany(Requisition::class, 'requisition_suppliers', 'supplier_id', 'requisition_id')
            ->withPivot('is_recommended', 'quoted_total', 'quote_reference_number')
            ->withTimestamps();
    }
}
