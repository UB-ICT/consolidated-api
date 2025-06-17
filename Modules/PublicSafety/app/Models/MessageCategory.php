<?php

namespace Modules\PublicSafety\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\PublicSafety\Models\Message;

class MessageCategory extends Model
{
    use HasFactory;
     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category',
    ];
    public $timestamps = false;


    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
