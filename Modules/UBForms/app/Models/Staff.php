<?php

namespace Modules\UBForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model; //Needs this inorder to use MongoDB
use Modules\UBForms\Models\User; // Commented out as it may be incorrect


/*
This is the staff model, the following fields are used to 
populate the 'staff' annual report in mongo.

Author: SW

*/

class Staff extends Model
{
    use HasFactory;

    protected $connection = 'firestore'; // Specify the Firestore connection if needed
    protected $collection = 'staff'; // Specify the collection name if different from the default

    protected $fillable = [
        'email',
        'userID',
        'name',
        'academicYearID',
        'department',
        'reportsTo',
        'deadline',
        'missionStatement',
        'strategicGoals',
        'accomplishments',
        'researchPartnerships',
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
