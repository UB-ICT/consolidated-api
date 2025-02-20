<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Message\Models\Message;

class IncidentFile extends Model
{
    use HasFactory;
    protected $fillable = [
        'path',
        'comment',
        'message_id',
    ];
    public $timestamps = false;


    
    public function incidentFile()
    {
        return $this->belongsTo(IncidentReport::class);
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
