<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use Modules\UBForms\Models\HumanResources;
use Modules\UBForms\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FirestoreService;

class HRStatistics extends Controller
{
    //Initialize

    private function initializeReport(string $email)
    {
        $report = [
            'email' => $email,
            'academicYearID' => "2023-2024",
            'department' => "",
            'deadline' => "",
            'numberOfStaff' => [ //From Github
                'fulltimeFaculty' => ['educationAndArts' => 0, 'managementAndSocialSciences' => 0, 'healthSciences' => 0, 'scienceAndTechnology' => 0, 'total' => 0],
                'adjunctFaculty' => ['educationAndArts' => 0, 'managementAndSocialSciences' => 0, 'healthSciences' => 0, 'scienceAndTechnology' => 0, 'total' => 0],
                'nonTeachingStaff' => ['educationAndArts' => 0, 'managementAndSocialSciences' => 0, 'healthSciences' => 0, 'scienceAndTechnology' => 0, 'total' => 0]
            ],
            'formSubmitted' => false,
        ];
        // Store in Firestore and get document ID
        $documentRef = FirestoreService::syncDocumentAndGetRef('humanResources', $report);
        return [
            'data' => $report,
            'id' => $documentRef->id()
        ];
    }

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

    //Create function doesn't pass the user email
    public function store(Request $request)
    {
        try {
            $data = $request->all(); //Adding this in the event things need to be validated later on    
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            $documentRef = FirestoreService::syncDocumentAndGetRef('humanResources', $data);
            $response = [
                'success' => true,
                'message' => "humanResources Report Created Successfully",
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
            $report = FirestoreService::getDocument('humanResources', $reportID);
            if ($report) {
                // Format success response
                $response = [
                    'success' => true,
                    'message' => 'Report data found successfully',
                    'data' => [
                        'report' => $report
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
    public function updateReport(Request $request)
    {
        try {
            $data = $request->all();
            if (!isset($data['id'])) {
                throw new \Exception('Report ID is required');
            }
            // Add updated timestamp
            $data['updated_at'] = now()->toDateTimeString();
            $success = FirestoreService::updateDocument('humanResources', $data['id'], $data);
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

    //Delete
    public function delReport(Request $request)
    {
        try {
            $id = $request->input('reportID');
            $success = FirestoreService::deleteDocument('humanResources', $id);
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
            $reports = FirestoreService::queryCollection('humanResources', 'email', '==', $user->email);
            if (!empty($reports)) {
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

    public function generateHRPdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            // Get report from Firestore
            $report = FirestoreService::getDocument('humanResources', $reportID);
            if (!$report) {
                return response()->json(['error' => 'Report not found'], 404);
            }
            // Generate PDF
            $pdf = PDF::loadView('UBForms::hrstatisticsreport', [
                'report' => $report,
                'user' => $user,
                'request' => $request
            ]);
            return $pdf->download('human_resources_report_' . $reportID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
