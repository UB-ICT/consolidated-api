<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\RequisitionFactory;

class Requisition extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['number', 'cost_center_id', 'supplier_id', 'status_id', 'currency_id', 'conversion_rate', 'total', 'stage_id'];

    protected $casts = [
        'date_prepared' => 'datetime:M d, Y',
    ];

    // protected static function newFactory(): RequisitionFactory
    // {
    //     // return RequisitionFactory::new();
    // }
}
