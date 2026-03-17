<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\AddressFactory;

class Address extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['supplier_id', 'street', 'city', 'district', 'postal_code', 'country_id'];

    // protected static function newFactory(): AddressFactory
    // {
    //     // return AddressFactory::new();
    // }
}
