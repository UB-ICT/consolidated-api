<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IncidentFile extends Model
{
    use HasFactory;

    protected $fillable = ['incident_report_id', 'path', 'name'];

/*************  ✨ Windsurf Command ⭐  *************/
/*******  17e21ed3-00fe-45b4-8831-ff5df6f5f24d  *******/
    public function incidentReport()
    {
        return $this->belongsTo(IncidentReport::class);
    }
}
