<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentStatus extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'incident_statuses';

    public $timestamps = false;

    protected $fillable = [
        'statuses',
    ];
}
