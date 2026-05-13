<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentReport extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'incident_reports';

    protected $fillable = [
        'report',
        'description',
        'disposition',
        'case_number',
        'action',
        'location',
        'uploaded_by',
        'frequency',
        'incident_reoccured',
        'incident_status_id',
        'user_id',
        'campus_id',
        'building_id',
        'incident_type_id',
    ];

    protected $casts = [
        'incident_reoccured' => 'datetime',
    ];

    public function incidentStatus(): BelongsTo
    {
        return $this->belongsTo(IncidentStatus::class, 'incident_status_id');
    }

    public function incidentFiles(): HasMany
    {
        return $this->hasMany(IncidentFile::class, 'incident_report_id');
    }
}
