<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentType extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'incident_types';

    public $timestamps = false;

    protected $fillable = [
        'icon',
        'type',
        'message',
    ];
}
