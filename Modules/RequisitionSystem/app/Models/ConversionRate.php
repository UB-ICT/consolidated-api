<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\ConversionRateFactory;

class ConversionRate extends Model
{
    use HasFactory;

    protected $connection = 'porsql';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['rate', 'currency_id'];
}
