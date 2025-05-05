<?php

namespace Modules\Message\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\PublicSafety\Models\MessageCategory;
use Modules\PublicSafety\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
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
        'timestamp' => 'datetime',
    ];
    public $timestamps = false;




    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function messageCategory()
    {
        return $this->belongsTo(MessageCategory::class, 'messageCategory_id');
    }

}
