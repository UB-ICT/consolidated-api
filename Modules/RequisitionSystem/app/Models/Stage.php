<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Modules\RequisitionSystem\Models\Pipeline;

class Stage extends Model
{
    protected $connection = 'porsql';

    public $timestamps = false;

    protected $fillable = ['name'];

    /**
     * Pipelines that utilize this specific stage.
     */
    public function pipelines(): BelongsToMany
    {
        return $this->belongsToMany(Pipeline::class, 'pipeline_stages', 'stage_id', 'pipeline_id');
    }
}
