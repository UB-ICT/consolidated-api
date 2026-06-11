<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{

    protected $connection = 'porsql';

    protected $fillable = ['name', 'contact_person', 'phone_number', 'email', 'TIN', 'status_id'];

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
