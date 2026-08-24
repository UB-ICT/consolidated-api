<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTerm extends Model
{

    protected $connection = 'porsql';

    protected $fillable = ['name'];

    /**
     * Get all suppliers using this payment term.
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'payment_term_id');
    }
}
