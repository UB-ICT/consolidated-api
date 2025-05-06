<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\PublicSafety\Models\User;

class UserStatus extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'userStatuses'
    ];
    public $timestamps = false;


    public function user()
    {
        return $this->hasMany(User::class);
    }


}

