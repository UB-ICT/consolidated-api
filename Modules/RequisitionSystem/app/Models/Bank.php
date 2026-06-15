<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{

    protected $connection = 'porsql';

    protected $fillable = ['name'];

    /**
     * Get all banking records tied to this bank.
     */
    public function supplierBanks(): HasMany
    {
        return $this->hasMany(SupplierBank::class, 'bank_id');
    }
}
