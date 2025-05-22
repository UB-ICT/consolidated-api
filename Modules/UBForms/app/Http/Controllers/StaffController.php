<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use Modules\UBForms\Models\Staff;
use Modules\PublicSafety\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FirestoreService;

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
        $report = [
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
        ];

        // Store in Firestore and get document ID
        $documentRef = FirestoreService::syncDocumentAndGetRef('staff', $report);

        return [
            'data' => $report,
            'id' => $documentRef->id()
        ];
    }

    //This will create the report and generate a report ID
    public function initialize(Request $request)
    {
        try {
            $user = $request->user();
            $report = $this->initializeReport($user->email);

            $response = [
                'success' => true,
                'message' => "Initialization Successful",
                'data' => [
                    'reportID' => $report['id']
                ],
            ];
        } catch (\Exception $e) {
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
        try {
            $data = $request->all();

            // Add timestamps
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();

            $documentRef = FirestoreService::syncDocumentAndGetRef('staff', $data);

            $response = [
                'success' => true,
                'message' => "Staff Report Created Successfully",
                'data' => [
                    'reportID' => $documentRef->id()
                ]
            ];
        } catch (\Exception $e) {
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
            $report = FirestoreService::getDocument('staff', $reportID);

            if ($report) {
                $response = [
                    'success' => true,
                    'message' => 'Report data found successfully',
                    'data' => [
                        'report' => $report
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }

        return response($response, 200);
    }


    public function updateReport(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['id'])) {
                throw new \Exception('Report ID is required');
            }

            // Add updated timestamp
            $data['updated_at'] = now()->toDateTimeString();

            $success = FirestoreService::updateDocument('staff', $data['id'], $data);

            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Report data updated successfully',
                    'data' => null
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }

        return response($response, 200);
    }

    public function delReport(Request $request)
    {
        try {
            $id = $request->input('reportID');
            $success = FirestoreService::deleteDocument('staff', $id);

            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Report data deleted successfully',
                    'data' => null
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
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
            $user = $request->user();
            $reports = FirestoreService::queryCollection('staff', 'email', '==', $user->email);

            if (!empty($reports)) {
                // Assuming we want the first report if multiple exist
                $report = $reports[0];

                $response = [
                    'success' => true,
                    'message' => 'Report data found successfully',
                    'data' => [
                        'report' => $report
                    ]
                ];
            } else {
                $report = $this->initializeReport($user->email);

                $response = [
                    'success' => true,
                    'message' => 'Report Initialized.',
                    'data' => [
                        'report' => $report
                    ],
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }

        return response($response, 200);
    }

}
