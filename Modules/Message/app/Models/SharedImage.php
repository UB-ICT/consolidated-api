<?php

namespace Modules\Message\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SharedImage extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'chat_id',
        'message_id',
        'url',
        'description'
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}