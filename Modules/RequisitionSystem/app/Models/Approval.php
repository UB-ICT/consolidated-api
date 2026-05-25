<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\ApprovalFactory;

class Approval extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['requisition_id', 'user_id', 'comments'];

    protected $casts = [
        'signed_at' => 'datetime:M d, Y',
    ];

    public $timestamps = false;

    // protected static function newFactory(): ApprovalFactory
    // {
    //     // return ApprovalFactory::new();
    // }
}
