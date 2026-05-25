<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\SupplierFactory;

class Supplier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'contact_person', 'phone_number', 'email', 'TIN', 'approval_status'];

    public $timestamps = false;

    // protected static function newFactory(): SupplierFactory
    // {
    //     // return SupplierFactory::new();
    // }
}
