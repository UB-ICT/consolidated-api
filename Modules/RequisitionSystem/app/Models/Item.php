<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\ItemFactory;

class Item extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['description', 'quantity', 'line_item_number', 'unit_cost', 'total', 'comments', 'requisition_id'];
}
