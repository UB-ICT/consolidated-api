<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\PublicSafety\Database\Factories\MessageFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender',
        'message_category_id',
        'images',
        'message',
        'location',
        'date_sent',
        'is_deleted',
        'type',
        'created_at',
        'updated_at',
    ];

    public function messageCategory()
    {
        return $this->belongsTo(MessageCategory::class);
    }
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender', 'id');
    }
}
