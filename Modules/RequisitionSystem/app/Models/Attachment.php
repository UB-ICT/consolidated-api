<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\AttachmentFactory;

class Attachment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['file_name', 'file_path', 'uploaded_by', 'requisition_id', 'supplier_id'];

    protected $casts = [
        'uploaded_at' => 'datetime:M d, Y',
    ];
}
