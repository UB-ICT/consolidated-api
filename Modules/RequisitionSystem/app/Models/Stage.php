<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\StageFactory;

class Stage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'pipeline_id'];

    public $timestamps = false;

    // protected static function newFactory(): StageFactory
    // {
    //     // return StageFactory::new();
    // }
}
