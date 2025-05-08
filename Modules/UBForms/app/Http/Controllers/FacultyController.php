<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faculty;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;


/*
This is the Faculty Controller responsible for doing 5 functions. 

The function initialize() creates the fields in mongo db for all academic annual reports 
that are to be submitted by Faculty.

The function store(), is similar but you would need to pass in all the fields with the 
information to properly create it in the database. 

The function getReport() retrieves the report based on the report ID. 

The function delReport() deleted the report based on the report ID. 

The last function updateReport() updates a report, it only updates the fields that are passed. 

Author: SW

*/

class FacultyController extends Controller
{
    //Initialize 

    private function initializeReport(string $email){
        return $reportData = Faculty::create([
            'email' => $email,
            'academicYearID' => "2023-2024",
            'faculty' =>  "",
            'units' =>  "",
            'deadline' =>  "",
            'departmentList' => '',
            'missionStatement' =>  "",
            'strategicGoals' =>  ['previousAcademicYear' => '', 'plans' => '', 'completionRate'=>''],
            'accomplishments'=>  ['accomplishmentList' => '', 'accomplishmentAdvancement' => '', 'mostImpactfulChange' => '', 'why' => '', 'applicableOpportunities' => ''],
            'researchPartnerships' =>  ['externalFunding' => '', 'researchPublications' => '', 'partnershipAgencies' => '', 'scholarships' => ''],
            'revisedAcademics' => ['programsOffered' => '', 'newProgrammesAdded' => '', 'revisedPrograms' => ''],
            'academicPrograms' =>  "",
            'courses' =>  ['totalNewCourses' => '', 'totalCoursesOnline' => '', 'totalCourseFaceToFace' => ''],
            'eliminatedAcademicPrograms' =>  "",
            'retentionOfStudents' =>  ['currentStudents' => '', 'transferStudents' => ''],
            'studentInternships' =>  "",
            'degreesConferred' =>  ['degreesConferredForMostRecentAcademicYear' => '', 'degreesConferredForMostRecentAcademicYearPerDepartment' => ''],
            'studentSuccess' =>  ['studentLearning' => '', 'studentClubs' => '', 'student1' => '', 'reason1' => '', 'student2' => '', 'reason2' => '', 'student3' => '', 'reason3' => ''],
            'activities' => Array(['eventId' =>  0, 'eventName' => '', 'personsInPicture' => '', 'pictureURL' => Array(['eventPicture' => '']), 'eventSummary' => '', 'eventMonth' => '']),
            'administrativeData' =>  ['fullTimeStaff' => '', 'partTimeStaff' => '', 'significantStaffChanges' => ''],
            'financialBudget' =>  ['fundingSources' => '', 'impactfulChanges' => ''],
            'meetings'=> Array(['meetingId' => 0, 'meetingType' => '', 'meetingDate' => '', 'meetingMinutesURL' => Array(['meetingURL' => ''])]),
            'formSubmitted' => false,
            'otherComments' =>  "",
            'formSubmitted' => false
        ]);
    }

    public function initialize(Request $request){

        try{

            $data = $request->all(); //Adding this in the event things need to be validated later on  

            $user = $request->user();

            if(!$user || !$user->email)
              return response (['success' => false, 'message' =>'User\'s email not found on AD.', 'data' => null], 400);

            $reportData = $this->initializeReport($user->email);

            $response = [
              'success' => true,
              'message' => "Initialization Successfull",
              'data' => [
                  'reportID' => $reportData->_id
              ],            
            ];
        }catch(\Exception $e){
            // If an error occurs, create an error response
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,            
            ]; 
        }

        return response($response, 201);

    }

    //Create 

    public function store(Request $request){

        $data = $request->all(); //Adding this in the event things need to be validated later on    

        try{

            $reportData = Faculty::create([
                'academicYearID' => $data['academicYearID'],
                'faculty' => $data['faculty'],
                'units' => $data['units'],
                'deadline' => $data['deadline'],
                'departmentList' => $data['departmentList'],
                'missionStatement' => $data['missionStatement'],
                'strategicGoals' => $data['strategicGoals'],
                'accomplishments'=> $data['accomplishments'],
                'researchPartnerships' => $data['researchPartnerships'],
                'revisedAcademics' => $data['revisedAcademics'],
                'academicPrograms' => $data['academicPrograms'],
                'courses' => $data['courses'],
                'eliminatedAcademicPrograms' => $data['eliminatedAcademicPrograms'],
                'retentionOfStudents' => $data['retentionOfStudents'],
                'studentInternships' => $data['studentInternships'],
                'degreesConferred' => $data['degreesConferred'],
                'studentSuccess' => $data['studentSuccess'],
                'activities' => $data['activities'],
                'administrativeData' => $data['administrativeData'],
                'financialBudget' => $data['financialBudget'],
                'meetings'=> $data['meetings'],
                'otherComments' => $data['otherComments'],
                'formSubmitted' => $data['formSubmitted']
            ]);

            $response = [
                'success' => true,
                'message' => "Faculty Report Created Successfully",
                'data' => [
                    'reportID' => $reportData->_id
                ],            
            ]; 

        }catch(\Exception $e){
            // If an error occurs, create an error response
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,            
            ]; 
        }
        
        return response($response, 201);        
    }

    //Read 

    public function getReport(Request $request, string $reportID){
        try {

            // $data = $request->all();
            // $id = $request->input('reportID');

            // Retrieve data based on conditions (assuming $request has the id parameter)
            $report = Faculty::where('_id', $reportID)->first();

            if ($report) {
                    // Format success response
                $response = [
                    'success' => true,
                    'message' => 'Report data found successfully',
                    'data' => [
                        'reportData' => $report 
                    ]
                ];
            } else {
                // Report not found
                $response = [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }

    } catch (\Exception $e) {
            // Exception occurred
        $response = [
            'success' => false,
            'message' => $e->getMessage(),
            'data' => null
        ];
    }
     // Return response with HTTP status code 201 (Created)
     return response($response, 200);

    }

    //Update

    public function updateReport(Request $request){
        try {

            $data = $request->all();
            // $id = $request->input('reportID');

            // Retrieve data based on conditions (assuming $request has the id parameter)
            $report = Faculty::where('_id', $data['_id'])->first();

            if ($report) {

                $report->academicYearID = $request->has('academicYearID') ? $data['academicYearID'] : $report->academicYearID;
                $report->faculty = $request->has('faculty') ? $data['faculty'] : $report->faculty;
                $report->units = $request->has('units') ? $data['units'] : $report->units;
                $report->deadline = $request->has('deadline') ? $data['deadline'] : $report->deadline;
                $report->missionStatement = $request->has('missionStatement') ? $data['missionStatement'] : $report->missionStatement;
                $report->strategicGoals = $request->has('strategicGoals') ? $data['strategicGoals'] : $report->strategicGoals;
                $report->accomplishments = $request->has('accomplishments') ? $data['accomplishments'] : $report->accomplishments;
                $report->researchPartnerships = $request->has('researchPartnerships') ? $data['researchPartnerships'] : $report->researchPartnerships;
                $report->revisedAcademics = $request->has('revisedAcademics') ? $data['revisedAcademics'] : $report->revisedAcademics;
                $report->academicPrograms = $request->has('academicPrograms') ? $data['academicPrograms'] : $report->academicPrograms;
                $report->courses = $request->has('courses') ? $data['courses'] : $report->courses;
                $report->eliminatedAcademicPrograms = $request->has('eliminatedAcademicPrograms') ? $data['eliminatedAcademicPrograms'] : $report->eliminatedAcademicPrograms;
                $report->retentionOfStudents = $request->has('retentionOfStudents') ? $data['retentionOfStudents'] : $report->retentionOfStudents;
                $report->studentInternships = $request->has('studentInternships') ? $data['studentInternships'] : $report->studentInternships;
                $report->degreesConferred = $request->has('degreesConferred') ? $data['degreesConferred'] : $report->degreesConferred;
                $report->studentSuccess = $request->has('studentSuccess') ? $data['studentSuccess'] : $report->studentSuccess;
                $report->activities = $request->has('activities') ? $data['activities'] : $report->activities;
                $report->administrativeData = $request->has('administrativeData') ? $data['administrativeData'] : $report->administrativeData;
                $report->financialBudget = $request->has('financialBudget') ? $data['financialBudget'] : $report->financialBudget;
                $report->meetings = $request->has('meetings') ? $data['meetings'] : $report->meetings;
                $report->formSubmitted = $request->has('formSubmitted') ? $data['formSubmitted'] : $report->formSubmitted;
                $report->otherComments = $request->has('otherComments') ? $data['otherComments'] : $report->otherComments;
                $report->formSubmitted = $request->has('formSubmitted') ? $data['formSubmitted'] : $report->formSubmitted;

                $report->save();
                    // Format success response
                $response = [
                    'success' => true,
                    'message' => 'Report data updated successfully',
                    'data' => null
                ];
            } else {
                // Report not found
                $response = [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }

    } catch (\Exception $e) {
            // Exception occurred
        $response = [
            'success' => false,
            'message' => $e->getMessage(),
            'data' => null
        ];
    }
     // Return response with HTTP status code 201 (Created)
     return response($response, 200);

    }

    //Delete

    public function delReport(Request $request){
        try {

            // $data = $request->all();
            $id = $request->input('reportID');

            // Retrieve data based on conditions (assuming $request has the id parameter)
            $report = Faculty::where('_id', $id)->first();

            if ($report) {

                $report->delete();
                    // Format success response
                $response = [
                    'success' => true,
                    'message' => 'Report data deleted successfully',
                    'data' => null
                ];
            } else {
                // Report not found
                $response = [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }

    } catch (\Exception $e) {
            // Exception occurred
        $response = [
            'success' => false,
            'message' => $e->getMessage(),
            'data' => null
        ];
    }
     // Return response with HTTP status code 201 (Created)
     return response($response, 200);

    }

    public function getReportByUser(Request $request){
        try {

            // $data = $request->all();
            // $id = $request->input('reportID');

            // Retrieve data based on conditions (assuming $request has the id parameter)

            $user = $request->user();

            if(!$user || !$user->email)
              return response (['success' => false, 'message' =>'User\'s email not found on AD.', 'data' => null], 400);

            $report = Faculty::where('email', $user->email)->first();

            if ($report) {
                    // Format success response
                $response = [
                    'success' => true,
                    'message' => 'Report data found successfully',
                    'data' => [
                        'reportData' => $report 
                    ]
                ];
            } else {
                $report = $this->initializeReport($user->email);

                // Report not found
                $response = [
                    'success' => true,
                    'message' => 'Report Initialized.',
                    'data' => [
                        'reportData' => $report 
                    ],
                ];
            }

        } catch (\Exception $e) {
                // Exception occurred
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
        // Return response with HTTP status code 201 (Created)
        return response($response, 200);

    }

    public function generateFacultyPdf(Request $request, string $reportID){ //Look into this a little more

        // Fetch data from MongoDB based on report ID
        $report = Faculty::find($reportID);
    

        // return $report;
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        // Get the user based on the email from the report
        $user = User::where('email', $report->email)->first();     

        // Generate PDF using data directly
        // $pdf = PDF::loadHTML($this->generateReportPdfHtml($report));
        $pdf = PDF::loadView('facultyReport', ['report' => $report, 'user' => $user]);


        // Return PDF as a response
        return $pdf->download('report_' . $report->id . '.pdf');
    }

    public function viewFacultyReport(Request $request, string $reportID){ //Look into this a little more

        // Fetch data from MongoDB based on report ID
        $report = Faculty::find($reportID);

        // return $report;
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $user = User::where('email', $report->email)->first();  

        return view('facultyReport', ['report' => $report, 'user' => $user]);
        
    }
}
