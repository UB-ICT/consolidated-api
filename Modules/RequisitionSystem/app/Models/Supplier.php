<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Supplier extends Model
{
    protected $connection = 'porsql';

    protected $fillable = [
        'name',
        'contact_person',
        'phone_number',
        'email',
        'TIN',
        'status_id',
    ];

    /**
     * Get the banking details for the supplier.
     * (Matches the single bank details block in your image_926da6.png UI)
     */
    public function bankAccount(): HasOne
    {
        return $this->hasOne(SupplierBank::class, 'supplier_id');
    }
}
