<?php

namespace Modules\UBForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model; //Needs this inorder to use MongoDB
use Modules\UBForms\Models\User;

/*
This is the HR model. Not originally part of any annual report, this 
is part of a custom report required only by 3 departments to submit. These 
were the fields relevant to that report. 

Author: SW

*/

class HumanResources extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'HRStatistics'; // Specify the collection name if different from the default

    protected $fillable = [
        'email',
        'userID',
        'academicYearID',
        'department',
        'reportsTo',
        'deadline',
        'numberOfStaff',
        'formSubmitted'
    ];

    public function user()
    {
        return User::where('email', $this->email)->first();
    }
}
