<?php

namespace Modules\RequisitionSystem\app\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PipelineStage extends Pivot
{
    protected $connection = 'porsql';

    protected $table = 'pipeline_stages';

    public $timestamps = false;

    protected $fillable = [
        'pipeline_id',
        'stage_id',
    ];
}
