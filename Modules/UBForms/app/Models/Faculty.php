<?php

namespace Modules\UBForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model; //Needs this inorder to use MongoDB 
use Modules\UBForms\Models\User;

/*
This is the Faculty model, the following fields are used to 
populate the 'Faculty' annual report in mongo.

Author: SW

*/

class Faculty extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'faculties'; // Specify the collection name if different from the default

    protected $fillable = [
        'email',
        'userID',
        'academicYearID',
        'faculty',
        'units',
        'deadline',
        'departmentList',
        'missionStatement',
        'strategicGoals',
        'accomplishments',
        'researchPartnerships',
        'revisedAcademics',
        'academicPrograms',
        'courses',
        'eliminatedAcademicPrograms',
        'retentionOfStudents',
        'studentInternships',
        'degreesConferred',
        'studentSuccess',
        'activities',
        'administrativeData',
        'financialBudget',
        'meetings',
        'formSubmitted',
        'otherComments'
    ];

    public function user()
    {
        return User::where('email', $this->email)->first();
    }
}
