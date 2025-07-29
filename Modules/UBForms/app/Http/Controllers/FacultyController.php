<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Log;

class FacultyController extends Controller
{
    //Initialize 

    private function initializeReport(string $email, string $name, string $academicYearID)
    {
        $report = [
            'email' => $email,
            'name' => $name,
            'academicYearID' => $academicYearID,
            'faculty' => "",
            'units' => [],
            'deadline' => "",
            'departmentList' => '',
            'missionStatement' => "",
            'strategicGoals' => ['previousAcademicYear' => '', 'plans' => '', 'completionRate' => ''],
            'accomplishments' => ['accomplishmentList' => '', 'accomplishmentAdvancement' => '', 'mostImpactfulChange' => '', 'why' => '', 'applicableOpportunities' => ''],
            'researchPartnerships' => ['externalFunding' => '', 'researchPublications' => '', 'partnershipAgencies' => '', 'scholarships' => ''],
            'revisedAcademics' => ['programsOffered' => '', 'newProgrammesAdded' => '', 'revisedPrograms' => ''],
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

        $reports = FirestoreService::queryCollection('faculties', 'email', '==', $email);
        // Then filter by academic year
        $filteredReports = array_filter($reports, function ($report) use ($academicYearID) {
            return isset($report['academicYearID']) && $report['academicYearID'] === $academicYearID;
        });

        if (!empty($filteredReports)) {
            return $filteredReports[0];
        }
        // Store in Firestore and get document ID
        $documentRef = FirestoreService::syncDocumentAndGetRef('faculties', $report);
        return array_merge($report, ['id' => $documentRef->id()]);
    }

    //Create
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            // Add timestamps
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            $documentRef = FirestoreService::syncDocumentAndGetRef('faculties', $data);
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
            $report = FirestoreService::getDocument('faculties', $reportID);
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
            $success = FirestoreService::updateDocument('faculties', $data['id'], $data);
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
            $success = FirestoreService::deleteDocument('faculties', $id);

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
            $settings = FirestoreService::getCollection('settings');

            // Get default academic year from first document in settings collection
            $defaultAcademicYear = ""; // fallback default
            if (!empty($settings)) {
                $firstSetting = $settings[0];
                if (isset($firstSetting['defaultAcademicYear'])) {
                    $defaultAcademicYear = $firstSetting['defaultAcademicYear'];
                }
            }

            if ($defaultAcademicYear == "") {
                $response = [
                    'success' => false,
                    'message' => "Default Academic Year not found",
                    'data' => null
                ];
                return response($response, 500);
            }

            $documents = FirestoreService::getCollection('faculties');
            $filteredDocuments = array_filter($documents, function ($document) use ($user, $defaultAcademicYear) {
                return isset($document['email']) && $document['email'] === $user->email && isset($document['academicYearID']) && $document['academicYearID'] === $defaultAcademicYear;
            });

            if (!empty($filteredDocuments)) {
                $report = array_values($filteredDocuments)[0]; // Get first record
                $response = [
                    'success' => true,
                    'message' => 'Report data found successfully',
                    'data' => $report
                ];
            } else {
                $report = $this->initializeReport($user->email, $user->name, $defaultAcademicYear);

                $response = [
                    'success' => true,
                    'message' => 'Report Initialized.',
                    'data' => $report
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
            $report = FirestoreService::getDocument('faculties', $reportID);
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