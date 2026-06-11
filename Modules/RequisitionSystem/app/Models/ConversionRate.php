<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;

class ConversionRate extends Model
{

    protected $connection = 'porsql';

    // protected $table = 'purchase_order_requisition.conversion_rates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['rate', 'currency_id'];
}
