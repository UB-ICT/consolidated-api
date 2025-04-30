<?php

namespace Modules\Message\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageFile extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'message_id',
        'url',
        'name',
        'type'
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}