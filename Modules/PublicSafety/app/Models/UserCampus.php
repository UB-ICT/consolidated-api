<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;

class UserCampus extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'user_campuses';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'campus_id',
        'primary_campus',
    ];

    protected $casts = [
        'primary_campus' => 'boolean',
    ];
}
