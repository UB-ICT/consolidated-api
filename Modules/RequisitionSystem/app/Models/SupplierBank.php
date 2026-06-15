<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBank extends Model
{

    protected $connection = 'porsql';

    // Explicitly defining the table name since it uses an underscore 
    // and doesn't follow standard single-word pluralization.
    protected $table = 'supplier_banks';

    protected $fillable = [
        'supplier_id',
        'bank_id',
        'account_number',
        'account_name',
        'address',
    ];

    /**
     * Get the supplier that owns this bank account.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the bank entity this account belongs to.
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }
}
