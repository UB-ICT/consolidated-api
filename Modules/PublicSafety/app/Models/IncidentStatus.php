<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentStatus extends Model
{
    use HasFactory;
    protected $fillable = [
       'statuses',
    ];
    public $timestamps = false;

}
