<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'campuses';

    public $timestamps = false;

    protected $fillable = [
        'campus',
    ];

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class, 'campus_id');
    }
}
