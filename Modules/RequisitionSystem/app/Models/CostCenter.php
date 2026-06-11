<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;

class CostCenter extends Model
{
    protected $connection = 'porsql';
    // 💡 Fix: Change this to match your migration table name precisely!
    protected $table = 'cost_centers';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'type'];
}
