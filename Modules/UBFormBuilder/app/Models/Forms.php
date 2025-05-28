<?php

namespace Modules\UBFormBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\UBFormBuilder\Database\Factories\FormsFactory;

class Forms extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): FormsFactory
    // {
    //     // return FormsFactory::new();
    // }
}
