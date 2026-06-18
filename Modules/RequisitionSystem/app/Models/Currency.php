<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Currency extends Model
{
    use HasFactory;

    protected $connection = 'porsql';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'symbol'];
}
