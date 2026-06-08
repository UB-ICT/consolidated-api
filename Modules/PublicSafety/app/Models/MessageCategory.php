<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;

class MessageCategory extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'message_categories';

    public $timestamps = false;

    protected $fillable = [
        'category',
    ];
}
