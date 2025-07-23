<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use Modules\UBForms\Models\Records;
use Modules\UBForms\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Log;

class RecordsStatistics extends Controller
{
    private function initializeReport(string $email, string $name, string $academicYearID)
    {
        $report = [
            'email' => $email,
            'name' => $name,
            'academicYearID' => $academicYearID,
            'department' => "",
            'deadline' => "",
            'currentStudentEnrollmentTrend' => ['associates' => 0, 'undergraduate' => 0, 'graduate' => 0, 'Total' => 0],
            'studentEnrollmentTrend' => array(
                ['academicYear' => '2022/2023', 'associate' => 0, 'undergraduate' => 0, 'graduate' => 0, 'other' => 0, 'Total' => 0],
                ['academicYear' => '2023/2024', 'associate' => 0, 'undergraduate' => 0, 'graduate' => 0, 'other' => 0, 'Total' => 0],
                ['academicYear' => '2024/2025', 'associate' => 0, 'undergraduate' => 0, 'graduate' => 0, 'other' => 0, 'Total' => 0],
            ),
            'enrollmentTrendPerFaculty' => array(
                ['academicYear' => '2022/2023', 'educationAndArts' => 0, 'managementAndSocialScience' => 0, 'healthScience' => 0, 'scienceAndTechnology' => 0],
                ['academicYear' => '2023/2024', 'educationAndArts' => 0, 'managementAndSocialScience' => 0, 'healthScience' => 0, 'scienceAndTechnology' => 0],
                ['academicYear' => '2024/2025', 'educationAndArts' => 0, 'managementAndSocialScience' => 0, 'healthScience' => 0, 'scienceAndTechnology' => 0],
            ),
            'graduationStatistics' => array(
                [
                    'academicYear' => "2022/2023",
                    'faculties' => array(
                        ['degree' => 'Education and Arts', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0], //A little ocd about this part but its okay
                        ['degree' => 'Management and Social Science', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Health Science', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Science and Technology', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                    )
                ],
                [
                    'academicYear' => "2023/2024",
                    'faculties' => array(
                        ['degree' => 'Education and Arts', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Management and Social Science', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Health Science', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Science and Technology', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                    )
                ],
                [
                    'academicYear' => "2024/2025",
                    'faculties' => array(
                        ['degree' => 'Education and Arts', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Management and Social Science', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Health Science', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Science and Technology', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                    )
                ]
            ),
            'studentOrigin' => ['Belize' => 0, 'CentralAmericanCountries' => 0, 'OtherCountries' => 0], //7.Origin of Students 
            'campusStatistics' => ['BelizeCity' => 0, 'Belmopan' => 0, 'PuntaGorda' => 0, 'CentralFarm' => 0, 'SatellitePrograms' => 0], //8.Campus Statistics
            'graduates' => ['graduatesByAge' => 0, 'graduatesByDistrict' => 0],//5 and 6 merged into one
            'formSubmitted' => false,
        ];

        $reports = FirestoreService::queryCollection('recordsStatistics', 'email', '==', $email);
        // Then filter by academic year
        $filteredReports = array_filter($reports, function ($report) use ($academicYearID) {
            return isset($report['academicYearID']) && $report['academicYearID'] === $academicYearID;
        });

        if (!empty($filteredReports)) {
            return $filteredReports[0];
        }
        // Store in Firestore and get document ID
        $documentRef = FirestoreService::syncDocumentAndGetRef('recordsStatistics', $report);
        return array_merge($report, ['id' => $documentRef->id()]);
    }

    //Create
    public function store(Request $request)
    {

        try {
            $data = $request->all();
            if (isset($data['currentStudentEnrollmentTrend'])) {
                $data['currentStudentEnrollmentTrend']['Total'] = $data['currentStudentEnrollmentTrend']['Total'] ??
                    ((int) ($data['currentStudentEnrollmentTrend']['associates'] ?? 0) +
                        (int) ($data['currentStudentEnrollmentTrend']['undergraduate'] ?? 0) +
                        (int) ($data['currentStudentEnrollmentTrend']['graduate'] ?? 0));
            }
            if (isset($data['studentEnrollmentTrend'])) {
                foreach ($data['studentEnrollmentTrend'] as &$trend) {
                    $trend['Total'] = $trend['Total'] ??
                        ((int) ($trend['associate'] ?? 0) +
                            (int) ($trend['undergraduate'] ?? 0) +
                            (int) ($trend['graduate'] ?? 0) +
                            (int) ($trend['other'] ?? 0));
                }
            }
            // Add timestamps
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            $documentRef = FirestoreService::syncDocumentAndGetRef('recordsStatistics', $data);
            $response = [
                'success' => true,
                'message' => "record Report Created Successfully",
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
            $report = FirestoreService::getDocument('recordsStatistics', $reportID);

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

    //Update

    public function updateReport(Request $request)
    {
        try {
            $data = $request->all();
            Log::info('Update Report Request:', $data);

            // Validate required fields
            if (!isset($data['id'])) {
                throw new \Exception('Report ID is required');
            }

            // Get existing document
            $existingDoc = FirestoreService::getDocument('recordsStatistics', $data['id']);
            // Log::debug('Existing Document:', $existingDoc);

            if (empty($existingDoc)) {
                throw new \Exception('Report not found in Firestore');
            }

            // Prepare updated data - merge existing with new updates
            $updatedData = array_merge($existingDoc, $data);

            // Handle currentStudentEnrollmentTrend
            if (isset($data['currentStudentEnrollmentTrend'])) {
                $current = $data['currentStudentEnrollmentTrend'];
                $updatedData['currentStudentEnrollmentTrend'] = [
                    'associates' => (int) ($current['associates'] ?? $existingDoc['currentStudentEnrollmentTrend']['associates'] ?? 0),
                    'undergraduate' => (int) ($current['undergraduate'] ?? $existingDoc['currentStudentEnrollmentTrend']['undergraduate'] ?? 0),
                    'graduate' => (int) ($current['graduate'] ?? $existingDoc['currentStudentEnrollmentTrend']['graduate'] ?? 0),
                    'Total' => (int) ($current['Total'] ??
                        (($current['associates'] ?? $existingDoc['currentStudentEnrollmentTrend']['associates'] ?? 0) +
                            ($current['undergraduate'] ?? $existingDoc['currentStudentEnrollmentTrend']['undergraduate'] ?? 0) +
                            ($current['graduate'] ?? $existingDoc['currentStudentEnrollmentTrend']['graduate'] ?? 0)))
                ];
            }

            // Handle studentEnrollmentTrend
            if (isset($data['studentEnrollmentTrend'])) {
                foreach ($data['studentEnrollmentTrend'] as $key => $trend) {
                    if (isset($existingDoc['studentEnrollmentTrend'][$key])) {
                        $updatedData['studentEnrollmentTrend'][$key] = [
                            'academicYear' => $trend['academicYear'] ?? $existingDoc['studentEnrollmentTrend'][$key]['academicYear'],
                            'associate' => (int) ($trend['associate'] ?? $existingDoc['studentEnrollmentTrend'][$key]['associate'] ?? 0),
                            'undergraduate' => (int) ($trend['undergraduate'] ?? $existingDoc['studentEnrollmentTrend'][$key]['undergraduate'] ?? 0),
                            'graduate' => (int) ($trend['graduate'] ?? $existingDoc['studentEnrollmentTrend'][$key]['graduate'] ?? 0),
                            'other' => (int) ($trend['other'] ?? $existingDoc['studentEnrollmentTrend'][$key]['other'] ?? 0),
                            'Total' => (int) ($trend['Total'] ??
                                (($trend['associate'] ?? $existingDoc['studentEnrollmentTrend'][$key]['associate'] ?? 0) +
                                    ($trend['undergraduate'] ?? $existingDoc['studentEnrollmentTrend'][$key]['undergraduate'] ?? 0) +
                                    ($trend['graduate'] ?? $existingDoc['studentEnrollmentTrend'][$key]['graduate'] ?? 0) +
                                    ($trend['other'] ?? $existingDoc['studentEnrollmentTrend'][$key]['other'] ?? 0)))
                        ];
                    }
                }
            }

            // Update timestamp
            $updatedData['updated_at'] = now()->toDateTimeString();

            // Update in Firestore
            $success = FirestoreService::updateDocument('recordsStatistics', $data['id'], $updatedData);

            if (!$success) {
                throw new \Exception('Firestore update operation failed');
            }

            return response()->json([
                'success' => true,
                'message' => 'Report updated successfully',
                'data' => null
            ]);

        } catch (\Exception $e) {
            Log::error('Update Report Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    //Delete
    public function delReport(Request $request)
    {
        try {
            $id = $request->input('reportID');
            $success = FirestoreService::deleteDocument('recordsStatistics', $id);
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

            $documents = FirestoreService::getCollection('recordsStatistics');
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

    public function generateRecordsPdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            // Get report from Firestore
            $report = FirestoreService::getDocument('recordsStatistics', $reportID);
            if (!$report) {
                return response()->json(['error' => 'Report not found'], 404);
            }
            // Generate PDF
            $pdf = PDF::loadView('UBForms::recordstatisticsreport', [
                'report' => $report,
                'user' => $user,
                'request' => $request
            ]);
            return $pdf->download('records_report_' . $reportID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
