<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'pipeline_id'];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }
}
