<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierBank extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['supplier_id', 'bank_id', 'account_number', 'account_name', 'address'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
