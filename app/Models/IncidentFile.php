<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IncidentFile extends Model
{
    use HasFactory;

    protected $fillable = ['incident_report_id', 'path', 'name'];

    public function incidentReport()
    {
        return $this->belongsTo(IncidentReport::class);
    }
}
