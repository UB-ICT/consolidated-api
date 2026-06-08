<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\RequisitionSystem\Database\Factories\UserStageFactory;

class UserStage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['user_id', 'stage_id'];

    // protected static function newFactory(): UserStageFactory
    // {
    //     // return UserStageFactory::new();
    // }
}
