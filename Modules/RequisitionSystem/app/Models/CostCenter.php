<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\CostCenterFactory;

class CostCenter extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'type'];

    public $timestamps = false;

    // protected static function newFactory(): CostCenterFactory
    // {
    //     // return CostCenterFactory::new();
    // }
}
