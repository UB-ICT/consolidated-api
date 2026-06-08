<?php

namespace Modules\UBForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model; //Needs this inorder to use MongoDB
use Modules\UBForms\Models\User;

class Records extends Model
{
    use HasFactory;

    protected $connection = 'firestore';
    protected $collection = 'recordsStatistics'; // Specify the collection name if different from the default

    //updated from Github
    protected $fillable = [
        'email',
        'name',
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
