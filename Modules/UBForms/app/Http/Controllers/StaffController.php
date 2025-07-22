<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FirestoreService;


class StaffController extends Controller
{
    public function initializeReport(string $email, string $name)
    {

        //get the default academic year from settings from firestore
        $settingsSnapshot = FirestoreService::getDocument('settings', '1pnMGp8R82EeHPsq2vkL');

        $defaultAcademicYear = '';
        if ($settingsSnapshot && isset($settingsSnapshot['defaultAcademicYear'])) {
            $defaultAcademicYear = $settingsSnapshot['defaultAcademicYear'];
            // Check if the academic year is set to 2024-2025
            if ($defaultAcademicYear !== '2024-2025') {
                throw new \Exception("The default academic year is not set to 2024-2025. Current value: " . $defaultAcademicYear);
            }
        } else {
            throw new \Exception("Default academic year is not configured in settings");
        }

        $report = [
            'email' => $email,
            "name" => $name,
            'academicYearID' => $defaultAcademicYear,
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
        $documentRef = FirestoreService::syncDocumentAndGetRef("staff", $report);
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
            $report = $this->initializeReport($user->email, $user->name);

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
                    'reportID' => $documentRef->id(),
                    // 'name' => $data['name'] ?? 'N/A',
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
                $report = $this->initializeReport($user->email, $user->name);
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

    public function generateStaffPdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            $report = FirestoreService::getDocument("staff", $reportID);
            if (!$report) {
                return response()->json(['error' => 'Report not found'], 404);
            }
            // Generate PDF
            $pdf = PDF::loadView('UBForms::staffreport', [
                'report' => $report,
                'user' => $user,
                'request' => $request
            ]);
            return $pdf->download('staff_report_' . $reportID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
