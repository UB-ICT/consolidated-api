<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;

class CostCenter extends Model
{
    protected $connection = 'porsql';

    protected $connection = 'porsql';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name'];
}
