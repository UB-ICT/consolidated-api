<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FirestoreService;

class FinanceStatistics extends Controller
{
    //Initialize 

    private function initializeReport(string $email, string $name, string $academicYearID)
    {
        $report = [
            'email' => $email,
            'name' => $name,
            'academicYearID' => $academicYearID,
            'department' => "",
            'deadline' => "",
            'income' => ['fundingFromGoB' => 0, 'contracts' => 0, 'researchGrants' => 0, 'endowmentAndInvestmentIncome' => 0, 'other' => 0, 'total' => 0],
            'tuitionFeesByFaculty' => [
                'FST' => 0,
                'FMSS' => 0,
                'FEA' => 0,
                'FHS' => 0,
                'SoM' => 0
            ],
            'expenditure' => ['teachingStaffCosts' => 0, 'nonTeachingStaffCosts' => 0, 'administrationCosts' => 0, 'capitalExpenditures' => 0, 'otherExpenditures' => 0],
            'investments' => ['projectInvestment1' => 0, 'projectInvestment2' => 0, 'projectInvestment3' => 0],
            'formSubmitted' => false,
        ];

        $reports = FirestoreService::queryCollection('FinanceStatistics', 'email', '==', $email);
        // Then filter by academic year
        $filteredReports = array_filter($reports, function ($report) use ($academicYearID) {
            return isset($report['academicYearID']) && $report['academicYearID'] === $academicYearID;
        });

        if (!empty($filteredReports)) {
            return $filteredReports[0];
        }
        // Store in Firestore and get document ID
        $documentRef = FirestoreService::syncDocumentAndGetRef('FinanceStatistics', $report);
        return array_merge($report, ['id' => $documentRef->id()]);
    }

    //Create 
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            $documentRef = FirestoreService::syncDocumentAndGetRef('FinanceStatistics', $data);
            $response = [
                'success' => true,
                'message' => "finance Report Created Successfully",
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
            $report = FirestoreService::getDocument('FinanceStatistics', $reportID);
            if ($report) {
                $response = [
                    'success' => true,
                    'message' => 'Report data found successfully',
                    // 'data' => [
                    //     'report' => $report
                    // ]
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
            if (!isset($data['id'])) {
                throw new \Exception('Report ID is required');
            }
            $data['updated_at'] = now()->toDateTimeString();
            $success = FirestoreService::updateDocument('FinanceStatistics', $data['id'], $data);
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
            $success = FirestoreService::deleteDocument('FinanceStatistics', $id);
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

            $documents = FirestoreService::getCollection('FinanceStatistics');
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

    public function generateFinancePdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            // Get report from Firestore
            $report = FirestoreService::getDocument('FinanceStatistics', $reportID);
            if (!$report) {
                return response()->json(['error' => 'Report not found'], 404);
            }
            // Generate PDF
            $pdf = PDF::loadView('UBForms::financestatisticsreport', [
                'report' => $report,
                'user' => $user,
                'request' => $request
            ]);
            return $pdf->download('finance_report_' . $reportID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
