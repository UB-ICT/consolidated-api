<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $connection = 'porsql';

    protected $fillable = [
        'file_name',
        'file_path',
        'uploaded_by',
        'requisition_id',
        'supplier_id',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime:M d, Y',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class, 'requisition_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
