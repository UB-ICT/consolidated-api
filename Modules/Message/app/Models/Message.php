<?php

namespace Modules\Message\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\IncidentFile;
use App\Models\MessageCategory;
use App\Models\Department;
use App\Models\User;
// use Modules\Message\Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;
    protected $fillable = [
        'user',
    ];

    protected $casts = [
        'attachments' => 'array',
        'read_at' => 'datetime',
    ];
    public $timestamps = false;


    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function messageCategory()
    {
        return $this->belongsTo(MessageCategory::class, 'messageCategory_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'message_id');
    }

    public function recipients()
    {
        return $this->belongsToMany(User::class, 'recipient', 'message_id', 'user_id');
    }
}
