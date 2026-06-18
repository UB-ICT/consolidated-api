<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Approval extends Model
{
    use HasFactory;

    protected $connection = 'porsql';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['requisition_id', 'user_id', 'comments', 'stage_id', 'status'];

    protected $casts = [
        'signed_at' => 'datetime:M d, Y',
    ];
}
