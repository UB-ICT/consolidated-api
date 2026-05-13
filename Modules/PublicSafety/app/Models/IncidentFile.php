<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentFile extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'incident_files';

    protected $fillable = [
        'incident_report_id',
        'path',
        'name',
    ];

    public function incidentReport(): BelongsTo
    {
        return $this->belongsTo(IncidentReport::class, 'incident_report_id');
    }
}
