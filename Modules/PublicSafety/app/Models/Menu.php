<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'menus';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'icon',
        'path',
    ];
}
