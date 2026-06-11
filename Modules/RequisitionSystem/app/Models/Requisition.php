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

    protected $connection = 'porsql';
    // Explicitly prefix the schema to the table name
    // protected $table = 'purchase_order_requisition.requisitions';

    protected $fillable = [
        'number',
        'cost_center_id',
        'supplier_id',
        'date_prepared',
        'status_id',
        'currency_id',
        'conversion_rate_id', // Must be here for Postman mass-assignment!
        'total',
        'stage_id',
    ];

    protected static function booted()
    {
        static::creating(function ($requisition) {
            $requisition->date_prepared = now();
        });
    }
}
