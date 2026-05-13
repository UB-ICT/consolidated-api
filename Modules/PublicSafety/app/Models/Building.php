<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Building extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'buildings';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'location',
        'campus_id',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }
}
