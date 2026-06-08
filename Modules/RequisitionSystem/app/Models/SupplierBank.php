<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\SupplierBankFactory;

class SupplierBank extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['supplier_id', 'bank_id', 'account_number', 'account_name', 'address'];

    // protected static function newFactory(): SupplierBankFactory
    // {
    //     // return SupplierBankFactory::new();
    // }
}
