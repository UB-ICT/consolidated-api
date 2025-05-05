<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    protected $fillable = [
       'departments',
    ];
    public $timestamps = false;


    public function members()
    {
        return $this->hasOne(DepartmentMember::class, 'department_id');
    }
}
