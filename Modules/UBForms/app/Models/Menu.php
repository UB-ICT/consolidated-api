<?php

namespace Modules\UBForms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UBForms\Models\User;

class Menu extends Model
{
    use HasFactory;

    protected $connection = 'firestore';
    protected $collection = 'menu'; // Specify the collection name if different from the default

    protected $fillable = [
        'name',
        'path',
        'icon',
        'order',
        'is_active',
    ];

    public function user()
    {
        return User::where('email', $this->email)->first();
    }
}
