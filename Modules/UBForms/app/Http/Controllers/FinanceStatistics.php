<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use Modules\UBForms\Models\Finance;
use Modules\UBForms\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FirestoreService;

class FinanceStatistics extends Controller
{
    //Initialize 

    private function initializeReport(string $email)
    {
        $report = [
            'email' => $email,
            'academicYearID' => "2023-2024",
            'department' => "",
            'deadline' => "",
            'income' => ['fundingFromGoB' => 0, 'tuitionFees' => 0, 'contracts' => 0, 'researchGrants' => 0, 'endowmentAndInvestmentIncome' => 0, 'other' => 0, 'total' => 0],
            'expenditure' => ['teachingStaffCosts' => 0, 'nonTeachingStaffCosts' => 0, 'administrationCosts' => 0, 'capitalExpenditures' => 0, 'otherExpenditures' => 0],
            'investments' => ['projectInvestment1' => 0, 'projectInvestment2' => 0, 'projectInvestment3' => 0],
            'formSubmitted' => false,
        ];
        // Store in Firestore and get document ID
        $documentRef = FirestoreService::syncDocumentAndGetRef('finance', $report);
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
                'message' => "Initialization Successfull",
                'data' => [
                    'reportID' => $report['id']
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

    //Create 
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            $documentRef = FirestoreService::syncDocumentAndGetRef('finance', $data);
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
            $report = FirestoreService::getDocument('finance', $reportID);
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
            if (!isset($data['id'])) {
                throw new \Exception('Report ID is required');
            }
            $data['updated_at'] = now()->toDateTimeString();
            $success = FirestoreService::updateDocument('finance', $data['id'], $data);
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
            $success = FirestoreService::deleteDocument('finance', $id);
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
            $reports = FirestoreService::queryCollection('finance', 'email', '==', $user->email);
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

    public function generateFinancePdf(Request $request, string $reportID)
    {
        $report = Finance::find($reportID);

        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        // Ensure all array keys exist with default values
        $defaultIncome = [
            'fundingFromGoB' => 0,
            'tuitionFees' => 0,
            'contracts' => 0,
            'researchGrants' => 0,
            'endowmentAndInvestmentIncome' => 0,
            'other' => 0,
            'total' => 0
        ];

        $defaultExpenditure = [
            'teachingStaffCosts' => 0,
            'nonTeachingStaffCosts' => 0,
            'administrationCosts' => 0,
            'capitalExpenditures' => '',
            'otherExpenditures' => ''
        ];

        // Merge with existing data
        $report->income = array_merge($defaultIncome, $report->income ?? []);
        $report->expenditure = array_merge($defaultExpenditure, $report->expenditure ?? []);

        // Handle potential null user
        $user = User::where('email', $report->email)->first() ?? new User([
            'name' => 'Unknown User',
            'email' => $report->email
        ]);

        $pdf = PDF::loadView('UBForms::financestatisticsreport', [
            'report' => $report,
            'user' => $user
        ]);

        return $pdf->download('report_' . $report->id . '.pdf');
    }

    public function viewFinanceReport(Request $request, string $reportID)
    { //Look into this a little more

        // Fetch data from MongoDB based on report ID
        $report = Finance::find($reportID);

        // return $report;
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $user = User::where('email', $report->email)->first();

        return view('FinanceStatisticsReport', ['report' => $report, 'user' => $user]);

    }

}
