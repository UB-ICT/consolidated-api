<?php

namespace Modules\Message\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\IncidentFile;
use App\Models\MessageCategory;
use App\Models\Department;
use App\Models\User;
// use Modules\Message\Database\Factories\MessageFactory;

class Message extends Model
{
    use HasFactory;
    protected $fillable = [
        'user',
        'message_category_id',
        'sender_id',
        'topic',
        'message',
        'location',
        'date_sent',
        'sender',
        'images',
        'is_archive',
        'is_deleted',
        'is_forwarded',
        'type',
        'incident_type_id',
        
    ];
    public $timestamps = false;


    // public function sender()
    // {
    //     return $this->belongsTo(User::class, 'sender_id');
    // }

  

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
