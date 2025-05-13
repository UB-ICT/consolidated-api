<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentType extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $fillable = [
        'type',
        'icon',
        'message',
    ];
    public $timestamps = false;

}
