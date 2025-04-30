<?php

namespace Modules\Message\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Message\Database\Factories\ChatFactory;

class Chat extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'last_text',
        'last_seen',
        'category',
        'role',
        'avatar_url'
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];
}
