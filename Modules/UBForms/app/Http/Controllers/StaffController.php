<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use Modules\UBForms\Models\Staff;
use Modules\PublicSafety\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Events\MongoDocumentCreated;


/*
This is the Staff Controller responsible for doing 5 functions. 

The function initialize() creates the fields in mongo db for all academic annual reports 
that are to be submitted by HOD.

The function store(), is similar but you would need to pass in all the fields with the 
information to properly create it in the database. 

The function getReport() retrieves the report based on the report ID. 

The function delReport() deleted the report based on the report ID. 

The last function updateReport() updates a report, it only updates the fields that are passed. 

Author: SW

*/

class StaffController extends Controller
{
    private function initializeReport(string $email)
    {
        return $reportData = Staff::create([
            'email' => $email,
            'academicYearID' => "2023-2024",
            'department' => "",
            'reportsTo' => "",
            'deadline' => "",
            'missionStatement' => "",
            'strategicGoals' => ['strategicGoalsUnderReview' => '', 'implmentationPlans' => '', 'plansToAchieveNotCompletedGoals' => '', 'strategicGoals' => ''],
            'accomplishments' => ['accomplishmentList' => '', 'accomplishmentAdvancement' => '', 'impactfulChange' => '', 'why' => '', 'applicableOpportunities' => ''],
            'researchPartnerships' => ['externalFunding' => '', 'researchPublications' => '', 'partnershipAgencies' => '', 'scholarships' => ''],
            'studentSuccess' => ['studentLearning' => '', 'studentClubs' => '', 'student1' => '', 'reason1' => '', 'student2' => '', 'reason2' => '', 'student3' => '', 'reason3' => ''],
            'activities' => array(['eventId' => 0, 'eventName' => "", 'personsInPicture' => '', 'pictureURL' => array(['eventPicture' => '']), 'eventSummary' => '', 'eventMonth' => '']),
            'administrativeData' => ['fullTimeStaff' => '', 'partTimeStaff' => '', 'significantStaffChanges' => ''],
            'financialBudget' => ['fundingSources' => '', 'significantBudgetChanges' => ''],
            'meetings' => array(['meetingId' => 0, 'meetingType' => '', 'meetingDate' => '', 'meetingMinutesURL' => array(['meetingURL' => ''])]),
            'formSubmitted' => false,
            'otherComments' => "",
            // 'formSubmitted' => false
        ]);
    }

    //This will create the report and generate a report ID
    public function initialize(Request $request)
    {

        try {

            $data = $request->all(); //Adding this in the event things need to be validated later on  

            $user = $request->user();

            $reportData = $this->initializeReport($user->email);

            // Dispatch the event
            event(new MongoDocumentCreated('staff', $reportData->toArray(), (string) $reportData->_id));


            $response = [
                'success' => true,
                'message' => "Initialization Successfull",
                'data' => [
                    'reportID' => $reportData->_id
                ],
            ];
        } catch (\Exception $e) {
            // If an error occurs, create an error response
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }

        return response($response, 201);

    }


    public function store(Request $request)
    {


        $data = $request->post(); //Adding this in the event things need to be validated later on


        try {
            // var_dump($data); return;

            $data = $request->all();

            $reportData = Staff::create([
                'academicYearID' => $data['academicYearID'],
                'department' => $data['department'],
                'reportsTo' => $data['reportsTo'],
                'deadline' => $data['deadline'],
                'missionStatement' => $data['missionStatement'],
                'strategicGoals' => $data['strategicGoals'],
                'accomplishments' => $data['accomplishments'],
                'researchPartnerships' => $data['researchPartnerships'],
                'studentSuccess' => $data['studentSuccess'],
                'activities' => $data['activities'],
                'administrativeData' => $data['administrativeData'],
                'financialBudget' => $data['financialBudget'],
                'meetings' => $data['meetings'],
                'otherComments' => $data['otherComments'],
                'formSubmitted' => $data['formSubmitted']
            ]);

            // Dispatch the event
            event(new MongoDocumentCreated('staff', $reportData->toArray(), (string) $reportData->_id));

            $response = [
                'success' => true,
                'message' => "Staff Report Created Successfully",
                'data' => [
                    'reportID' => $reportData->_id
                ],
            ];

        } catch (\Exception $e) {
            // If an error occurs, create an error response
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }

        return response($response, 201);

    }

    public function getReport(Request $request, string $reportID)
    {
        try {

            // Retrieve data based on conditions (assuming $request has the id parameter)
            $report = Staff::where('_id', $reportID)->first();

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

    public function delReport(Request $request)
    {
        try {

            // $data = $request->all();
            $id = $request->input('reportID');

            // Retrieve data based on conditions (assuming $request has the id parameter)
            $report = Staff::where('_id', $id)->first();

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

    public function updateReport(Request $request)
    {
        try {

            $data = $request->all();
            // $id = $request->input('reportID');

            // Retrieve data based on conditions (assuming $request has the id parameter)
            $report = Staff::where('_id', $data['_id'])->first();

            if ($report) {

                $report->academicYearID = $request->has('academicYearID') ? $data['academicYearID'] : $report->academicYearID;
                $report->department = $request->has('department') ? $data['department'] : $report->department;
                $report->reportsTo = $request->has('reportsTo') ? $data['reportsTo'] : $report->reportsTo;
                $report->deadline = $request->has('deadline') ? $data['deadline'] : $report->deadline;
                $report->missionStatement = $request->has('missionStatement') ? $data['missionStatement'] : $report->missionStatement;
                $report->strategicGoals = $request->has('strategicGoals') ? $data['strategicGoals'] : $report->strategicGoals;
                $report->accomplishments = $request->has('accomplishments') ? $data['accomplishments'] : $report->accomplishments;
                $report->researchPartnerships = $request->has('researchPartnerships') ? $data['researchPartnerships'] : $report->researchPartnerships;
                $report->studentSuccess = $request->has('studentSuccess') ? $data['studentSuccess'] : $report->studentSuccess;
                $report->activities = $request->has('activities') ? $data['activities'] : $report->activities;
                $report->administrativeData = $request->has('administrativeData') ? $data['administrativeData'] : $report->administrativeData;
                $report->financialBudget = $request->has('financialBudget') ? $data['financialBudget'] : $report->financialBudget;
                $report->meetings = $request->has('meetings') ? $data['meetings'] : $report->meetings;
                $report->formSubmitted = $request->has('formSubmitted') ? $data['formSubmitted'] : $report->formSubmitted;
                $report->otherComments = $request->has('otherComments') ? $data['otherComments'] : $report->otherComments;
                $report->formSubmitted = $request->has('formSubmitted') ? $data['formSubmitted'] : $report->formSubmitted;

                $report->save();

                // Dispatch the event for updates
                event(args: new MongoDocumentCreated('staff', $report->toArray(), (string) $report->_id));

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

    public function generateStaffPdf(Request $request, string $reportID)
    {
        $report = Staff::find($reportID);

        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $user = User::where('email', $report->email)->first();

        // Corrected view reference with module namespace
        $pdf = PDF::loadView('UBForms::staffreport', [
            'report' => $report,
            'user' => $user,
            'request' => $request
        ]);

        return $pdf->download('report_' . $report->id . '.pdf');
    }




    public function viewStaffReport(Request $request, string $reportID)
    { //Look into this a little more

        // Fetch data from MongoDB based on report ID
        $report = Staff::find($reportID);

        // return $report;
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $user = User::where('email', $report->email)->first();

        return view('staffReport', ['report' => $report, 'user' => $user]);

    }


    public function getReportByUser(Request $request)
    {
        try {
            // Retrieve data based on conditions (assuming $request has the id parameter)

            $user = $request->user();

            $report = Staff::where('email', $user->email)->first();

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

}
