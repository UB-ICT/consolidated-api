<?php

namespace Modules\UBForms\Http\Controllers;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use App\Services\FirestoreService;

class FacultyController extends Controller
{
    //Initialize 

    private function initializeReport(string $email)
    {
        $report = [
            'email' => $email,
            'academicYearID' => "2023-2024",
            'faculty' => "",
            'units' => [],
            'deadline' => "",
            'departmentList' => '',
            'missionStatement' => "",
            'strategicGoals' => ['previousAcademicYear' => '', 'plans' => '', 'completionRate' => ''],
            'accomplishments' => ['accomplishmentList' => '', 'accomplishmentAdvancement' => '', 'mostImpactfulChange' => '', 'why' => '', 'applicableOpportunities' => ''],
            'researchPartnerships' => ['externalFunding' => '', 'researchPublications' => '', 'partnershipAgencies' => '', 'scholarships' => ''],
            'revisedAcademics' => ['programsOffered' => '', 'newProgrammesAdded' => '', 'revisedPrograms' => ''],
            'academicPrograms' => "",
            'courses' => ['totalNewCourses' => '', 'totalCoursesOnline' => '', 'totalCourseFaceToFace' => ''],
            'eliminatedAcademicPrograms' => "",
            'retentionOfStudents' => ['currentStudents' => '', 'transferStudents' => ''],
            'studentInternships' => "",
            'degreesConferred' => ['degreesConferredForMostRecentAcademicYear' => '', 'degreesConferredForMostRecentAcademicYearPerDepartment' => ''],
            'studentSuccess' => ['studentLearning' => '', 'studentClubs' => '', 'student1' => '', 'reason1' => '', 'student2' => '', 'reason2' => '', 'student3' => '', 'reason3' => ''],
            'activities' => array(['eventId' => 0, 'eventName' => '', 'personsInPicture' => '', 'pictureURL' => array(['eventPicture' => '']), 'eventSummary' => '', 'eventMonth' => '']),
            'administrativeData' => ['fullTimeStaff' => '', 'partTimeStaff' => '', 'significantStaffChanges' => ''],
            'financialBudget' => ['fundingSources' => '', 'impactfulChanges' => ''],
            'meetings' => array(['meetingId' => 0, 'meetingType' => '', 'meetingDate' => '', 'meetingMinutesURL' => array(['meetingURL' => ''])]),
            'formSubmitted' => false,
            'otherComments' => "",
        ];
        // Store in Firestore and get document ID
        $documentRef = FirestoreService::syncDocumentAndGetRef('faculty', $report);
        return [
            'data' => $report,
            'id' => $documentRef->id()
        ];
    }

    public function initialize(Request $request)
    {
        try {
            $user = $request->user(); //Adding this in the event things need to be validated later on  
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

    //Create 

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            // Add timestamps
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();

            $documentRef = FirestoreService::syncDocumentAndGetRef('faculty', $data);

            $response = [
                'success' => true,
                'message' => "faculty Report Created Successfully",
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

    //Read 

    public function getReport(Request $request, string $reportID)
    {
        try {
            $report = FirestoreService::getDocument('faculty', $reportID);
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
        // Return response with HTTP status code 201 (Created)
        return response($response, 200);
    }

    //Update

    public function updateReport(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['id'])) {
                throw new \Exception('Report ID is required');
            }

            // Add updated timestamp
            $data['updated_at'] = now()->toDateTimeString();

            $success = FirestoreService::updateDocument('faculty', $data['id'], $data);

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
        // Return response with HTTP status code 201 (Created)
        return response($response, 200);

    }

    //Delete

    public function delReport(Request $request)
    {
        try {
            $id = $request->input('reportID');
            $success = FirestoreService::deleteDocument('faculty', $id);

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
        // Return response with HTTP status code 201 (Created)
        return response($response, 200);

    }

    public function getReportByUser(Request $request)
    {
        try {
            $user = $request->user();
            $reports = FirestoreService::queryCollection('faculty', 'email', '==', $user->email);

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

    public function generateFacultyPdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            // Get report from Firestore
            $report = FirestoreService::getDocument('faculty', $reportID);
            if (!$report) {
                return response()->json(['error' => 'Report not found'], 404);
            }
            // Generate PDF
            $pdf = PDF::loadView('UBForms::facultyreport', [
                'report' => $report,
                'user' => $user,
                'request' => $request
            ]);
            return $pdf->download('faculty_report_' . $reportID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
