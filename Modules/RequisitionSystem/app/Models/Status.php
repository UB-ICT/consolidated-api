<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $connection = 'porsql';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name'];
}
