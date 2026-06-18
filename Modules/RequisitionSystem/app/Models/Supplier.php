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
        'TIN',
        'status_id',
        'notes',
        'approved_by_user_id',
    ];

    public function scopeSelectable(Builder $query): Builder
    {
        $deletedStatusId = Status::where('name', SupplierStatus::DELETED)->value('id');

        if (!$deletedStatusId) {
            return $query;
        }

        return $query->where('status_id', '!=', $deletedStatusId);
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
