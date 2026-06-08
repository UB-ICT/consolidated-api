<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'user_statuses';

    public $timestamps = false;

    protected $fillable = [
        'userStatuses',
    ];
}
