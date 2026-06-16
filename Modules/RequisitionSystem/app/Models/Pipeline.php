<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pipeline extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'porsql';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name'];

    /**
     * Stages assigned to this pipeline ordered sequentially.
     */
    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(Stage::class, 'pipeline_stages', 'pipeline_id', 'stage_id')
            ->withPivot('sequence')
            ->orderBy('pipeline_stages.sequence', 'asc');
    }
}