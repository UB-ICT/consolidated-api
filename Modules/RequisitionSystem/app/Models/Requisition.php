<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Requisition extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'number',
        'cost_center_id',
        'supplier_id',
        'date_prepared',
        'status_id',
        'currency_id',
        'conversion_rate_id', // Note the _id suffix change
        'total',
        'stage_id',
    ];

    protected $casts = [
        'date_prepared' => 'datetime:M d, Y',
    ];

    protected static function booted()
    {
        static::creating(function ($requisition) {
            $requisition->date_prepared = now();
        });
    }
}
