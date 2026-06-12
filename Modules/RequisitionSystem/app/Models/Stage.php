<?php

namespace Modules\RequisitionSystem\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// 👇 Fixed these paths to include '\app'
use Modules\RequisitionSystem\Models\Pipeline;
use Modules\RequisitionSystem\app\Models\Logs;

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

    /**
     * Logs tied to this stage.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Logs::class, 'stage_id');
    }
}
