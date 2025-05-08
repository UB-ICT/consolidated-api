<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use MongoDB\Laravel\Eloquent\Model; //Needs this inorder to use MongoDB
use MongoDB\Laravel\Relations\BelongsTo;
use App\Models\User;

/*
This is the HR model. Not originally part of any annual report, this 
is part of a custom report required only by 3 departments to submit. These 
were the fields relevant to that report. 

Author: SW

*/

class Records extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'recordsStatistics'; // Specify the collection name if different from the default

    //updated from Github
    protected $fillable = [
        'email',
        'userID',
        'academicYearID',
        'department',
        'reportsTo',
        'deadline',
        'currentStudentEnrollmentTrend',
        'studentEnrollmentTrend',
        'enrollmentTrendPerFaculty',
        'graduationStatistics',
        'studentOrigin',
        'campusStatistics',
        'graduates',
        'formSubmitted'
    ];

    public function user()
    {
        return User::where('email', $this->email)->first();
    }
}
