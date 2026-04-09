<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\UserFactory;

class User extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'email', 'type', 'cost_center_id'];
    
    public $timestamps = false;

    // protected static function newFactory(): UserFactory
    // {
    //     // return UserFactory::new();
    // }
}
