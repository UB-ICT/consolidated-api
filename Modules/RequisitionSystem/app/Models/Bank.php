<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\BankFactory;

class Bank extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name'];

    public $timestamps = false;

    // protected static function newFactory(): BankFactory
    // {
    //     // return BankFactory::new();
    // }
}
