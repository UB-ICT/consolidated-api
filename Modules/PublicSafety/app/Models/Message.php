<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'messages';

    protected $fillable = [
        'profile_pic',
        'sender',
        'message_category_id',
        'images',
        'message',
        'location',
        'date_sent',
        'is_deleted',
        'type',
    ];

    protected $casts = [
        'date_sent' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    public function messageCategory(): BelongsTo
    {
        return $this->belongsTo(MessageCategory::class, 'message_category_id');
    }
}
