<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class AnonymousController extends Controller
{
    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'anonymousReports';

    public function initialize(Request $request)
    {
        try {

            $id = 'anonymous-' . Str::uuid();

            $defaultAnonymousReport = [
                'id' => $id,
                'caseNumber' => $this->generateCaseNumber(),
                'category' => '',
                'location' => '',
                'reports' => '',
                'formSubmitted' => false,
                'uploadedBy' => $id,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];

            Log::info('Initializing Anonymous Report:', $defaultAnonymousReport);

            $documentRef = FirestoreService::syncDocumentAndGetRef(
                $this->collectionName,
                $defaultAnonymousReport
            );

            return array_merge($defaultAnonymousReport, [
                'id' => $documentRef->id()
            ]);
        } catch (\Exception $e) {
            Log::error('Anonymous report initialization failed: ' . $e->getMessage());
            return response()->json(['error' => 'Initialization failed'], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $anonymousReport = FirestoreService::getCollection($this->collectionName);
            $response = [
                'successs' => true,
                'message' => 'anonymous reports retrieved successfully',
                'data' => $anonymousReport
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
        return response($response, 200);
    }

    //create/store
    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'category' => 'required|string',
                'location' => 'required|string',
                'reports' => 'required|string',
            ]);

            // Prepare the data to save
            $anonymousData = $request->all();
            $anonymousData['created_at'] = now()->toDateTimeString();
            $anonymousData['updated_at'] = now()->toDateTimeString();

            // Save the document in Firestore and get the reference
            $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $anonymousData);

            // Add Firestore document ID to the incident data
            $anonymousData['id'] = $documentRef->id();

            // Update the document to include its ID field
            $documentRef->update([
                ['path' => 'id', 'value' => $anonymousData['id']]
            ]);

            // Return only the newly created Anonymous Report
            $response = [
                'success' => true,
                'message' => 'Anonymous Report Created Successfully',
                'data' => $anonymousData
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

    //read
    public function show(Request $request, string $anonymousReportID)
    {
        try {
            $anonymousReport = FirestoreService::getDocument($this->collectionName, $anonymousReportID);
            if ($anonymousReport) {
                $response = [
                    'success' => true,
                    'message' => 'Anonymous Report found',
                    'data' => $anonymousReport
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Anonymous Report not found',
                    'data' => null,
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
        // Return response with HTTP status code 201 (Created)
        return response()->json($anonymousReport, 200);
    }

    //update
    public function update(Request $request, string $id)
    {
        try {
            $data = $request->only([
                'category',
                'location',
                'reports',
            ]);
            $data['updated_at'] = now()->toDateTimeString(); // Always track update time

            // Update the document in Firestore
            $success = FirestoreService::updateDocument($this->collectionName, $id, $data);

            if ($success) {
                // Fetch the updated document to return
                $updatedReport = FirestoreService::getDocument($this->collectionName, $id);

                $response = [
                    'success' => true,
                    'message' => 'Anonymous report updated successfully',
                    'data' => $updatedReport
                ];

                Log::info('Updated Anonymous report: ', $updatedReport);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Anonymous report not found',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            Log::error('Anonymous report update error: ' . $e->getMessage());
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }

        return response($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $success = FirestoreService::deleteDocument($this->collectionName, $id);

            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Anonymous report data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Anonymous report not found',
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
        return response()->json($response, 200);
    }

    private function generateCaseNumber(): string
    {
        $prefix = "ANON-";

        // Get all Anonymous reports for today
        $reports = FirestoreService::getCollection($this->collectionName);

        $lastNumber = 0;

        if (is_array($reports)) {
            foreach ($reports as $report) {
                if (
                    isset($report['caseNumber']) &&
                    str_starts_with($report['caseNumber'], $prefix)
                ) {
                    // Extract numeric part
                    $number = (int) substr($report['caseNumber'], -4);
                    $lastNumber = max($lastNumber, $number);
                }
            }
        }

        $nextNumber = $lastNumber + 1;

        return sprintf('%s%04d', $prefix, $nextNumber);
    }

    public function generateAnonymousReportPdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            $anonymousReport = FirestoreService::getDocument($this->collectionName, $reportID);
            if (!$anonymousReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anonymous Report not found',
                    'data' => null,
                ], 404);
            }

            // Load the view and pass the incident report data
            $pdf = Pdf::loadView('publicsafety::anonymousreport', [
                'anonymousReport' => $anonymousReport,
                'user' => $user,
                'request' => $request
            ]);

            // Return the generated PDF as a download
            return $pdf->download('anonymous_report_' . $reportID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getUnsubmittedAnonymousReports(Request $request)
    {
        try {
            $userName = $request->user()->name ?? '';

            $unsubmitted = FirestoreService::getCollectionWhere(
                $this->collectionName,
                'uploadedBy',
                '=',
                $userName
            );

            // Filter for reports where formSubmitted == false
            $unsubmittedReport = collect($unsubmitted)
                ->firstWhere('formSubmitted', false);

            if ($unsubmittedReport) {
                return response()->json([
                    'success' => true,
                    'message' => 'Unsubmitted Anonymous report found',
                    'data' => $unsubmittedReport,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No unsubmitted Anonymous report found',
                'data' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
