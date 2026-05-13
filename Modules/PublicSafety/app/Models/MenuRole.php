<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;

class MenuRole extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'menu_roles';

    public $timestamps = false;

    protected $fillable = [
        'menu_id',
        'role_id',
    ];
}
