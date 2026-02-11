<?php

namespace Modules\PublicSafety\Http\Controllers;


use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class IncidentReportController extends Controller
{

    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'incidentReports';

    public function initialize(Request $request)
    {
        try {
            $defaultReport = [
                'title' => '',
                'action' => '',
                'caseNumber' => $this->generateCaseNumber(),
                'description' => '',
                'incidentReportStatus' => '',
                'incidentType' => '',
                'buildingName' => '',
                'uploadedBy' => $request->user()->name ?? '', // Assuming you have authentication
                'campus' => "",
                'date' => "",
                'time' => "",
                'incidentFiles' => [],
                'reportedBy' => "",
                'contact' => "",
                'witnesses' => "",
                'formSubmitted' => false,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];

            Log::info('Initializing Incident Report: ', $defaultReport);
        } catch (\Exception $e) {
        }
        $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $defaultReport);
        return array_merge($defaultReport, ['id' => $documentRef->id()]);
    }

    public function index(Request $request)
    {
        try {
            $incidentReports = FirestoreService::getCollection($this->collectionName);
            $response = [
                'success' => true,
                'message' => 'Incident Reports retrieved successfully',
                'data' => $incidentReports
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
                'title' => 'required|string',
                'action' => 'required|string',
                'campus' => 'required|string',
                'description' => 'required|string',
                'caseNumber' => 'required|string',
                'incidentReportStatus' => 'required|string',
                'incidentType' => 'required|string',
                'buildingName' => 'required|string',
                'incidentFiles' => 'nullable|array',
                'uploadedBy' => 'required|string',
                'date' => 'required|string',
                'time' => 'required|string',
                'reportedBy' => 'nullable|string',
                'contact' => 'nullable|string',
                'witnesses' => 'nullable|string',
                'formSubmitted' => 'required|boolean',
            ]);

            // Prepare the data to save
            $incidentData = $request->all();
            $incidentData['created_at'] = now()->toDateTimeString();
            $incidentData['updated_at'] = now()->toDateTimeString();

            // Save the document in Firestore and get the reference
            $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $incidentData);

            // Add Firestore document ID to the incident data
            $incidentData['id'] = $documentRef->id();

            // Update the document to include its ID field
            $documentRef->update([
                ['path' => 'id', 'value' => $incidentData['id']]
            ]);

            // Return only the newly created incident report
            $response = [
                'success' => true,
                'message' => 'Incident Report Created Successfully',
                'data' => $incidentData
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
    public function show(Request $request, string $incidentReportID)
    {
        try {
            $incidentReport = FirestoreService::getDocument($this->collectionName, $incidentReportID);
            if ($incidentReport) {
                $response = [
                    'success' => true,
                    'message' => 'incident Report found',
                    'data' => $incidentReport
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Incident Report not found',
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
        return response()->json($incidentReport, 200);
    }

    //update
    public function update(Request $request, string $id)
    {
        try {
            $data = $request->only([
                'title',
                'action',
                'campus',
                'description',
                'caseNumber',
                'incidentReportStatus',
                'incidentType',
                'buildingName',
                'incidentFiles',
                'uploadedBy',
                'date',
                'time',
                'reportedBy',
                'contact',
                'witnesses',
                'formSubmitted',
            ]);
            $data['updated_at'] = now()->toDateTimeString(); // Always track update time

            // Update the document in Firestore
            $success = FirestoreService::updateDocument($this->collectionName, $id, $data);

            if ($success) {
                // Fetch the updated document to return
                $updatedReport = FirestoreService::getDocument($this->collectionName, $id);

                $response = [
                    'success' => true,
                    'message' => 'Incident Report updated successfully',
                    'data' => $updatedReport
                ];

                Log::info('Updated Incident Report: ', $updatedReport);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Incident Report not found',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            Log::error('Incident Report update error: ' . $e->getMessage());
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
                    'message' => 'Incident Report data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Incident Report not found',
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


    /**
     * Verify all referenced documents exist
     */
    private function verifyReferencesExist(array $data)
    {
        $references = [
            'incidentStatusId' => self::COLLECTION_PREFIX . 'incidentStatuses',
            'campusId' => self::COLLECTION_PREFIX . 'campuses',
            'buildingId' => self::COLLECTION_PREFIX . 'buildings',
            'incidentTypeId' => self::COLLECTION_PREFIX . 'incidentTypes'
        ];

        foreach ($references as $field => $collection) {
            if (!empty($data[$field])) {
                $exists = FirestoreService::getDocument($collection, $data[$field]);
                if (!$exists) {
                    throw new \Exception("The specified {$field} does not exist");
                }
            }
        }
    }

    /**
     * Generate a sequential case number (Firestore-safe)
     * Format: INC-YYYYMMDD-0001
     */
    private function generateCaseNumber(): string
    {
        $date = date('Ymd');
        $prefix = "INC-$date-";

        // Get all incident reports for today
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

    public function getTotalIncidentReport()
    {
        try {
            // 1️⃣ Get all incident reports from Firestore
            $incidentReports = FirestoreService::getCollection($this->collectionName);

            $total = 0;

            if (is_array($incidentReports)) {
                foreach ($incidentReports as $report) {
                    // 2️⃣ Only count submitted reports
                    if (isset($report['formSubmitted']) && $report['formSubmitted'] === true) {
                        $total++;
                    }
                }
            }

            $response = [
                'success' => true,
                'message' => 'Total submitted incident reports retrieved successfully',
                'data' => [
                    'total' => $total
                ]
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


    public function generateIncidentReportPdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            $incidentReport = FirestoreService::getDocument($this->collectionName, $reportID);
            if (!$incidentReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incident Report not found',
                    'data' => null,
                ], 404);
            }

            // Load the view and pass the incident report data
            $pdf = Pdf::loadView('publicsafety::incidentreport', [
                'incidentReport' => $incidentReport,
                'user' => $user,
                'request' => $request
            ]);

            // Return the generated PDF as a download
            return $pdf->download('incident_report_' . $reportID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getUnsubmittedIncidentReports(Request $request)
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
                    'message' => 'Unsubmitted incident report found',
                    'data' => $unsubmittedReport,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No unsubmitted incident report found',
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

    public function getActiveIncidentReports()
    {
        try {
            // 1️⃣ Get all incident logs from Firestore
            $incidentLogs = FirestoreService::getCollection($this->collectionName);

            $activeCount = 0;

            if (is_array($incidentLogs)) {
                foreach ($incidentLogs as $log) {
                    // ✅ Only count submitted forms
                    if (!isset($log['formSubmitted']) || !$log['formSubmitted']) continue;

                    // ✅ Check if incident is "Investigating" or any "active" status
                    if (isset($log['incidentReportStatus']) && $log['incidentReportStatus'] === 'Investigating') {
                        $activeCount++;
                    }
                }
            }

            $response = [
                'success' => true,
                'message' => 'Active incidents retrieved successfully',
                'data' => ['totalActive' => $activeCount]
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

    public function getResolvedIncidentReports()
    {
        try {
            // 1️⃣ Get all incident logs from Firestore
            $incidentLogs = FirestoreService::getCollection($this->collectionName);

            $resolvedCount = 0;

            if (is_array($incidentLogs)) {
                foreach ($incidentLogs as $log) {
                    // ✅ Only count submitted forms
                    if (!isset($log['formSubmitted']) || !$log['formSubmitted']) continue;

                    // ✅ Check if incident is "Investigating" or any "active" status
                    if (isset($log['incidentReportStatus']) && $log['incidentReportStatus'] === 'Resolved') {
                        $resolvedCount++;
                    }
                }
            }

            $response = [
                'success' => true,
                'message' => 'Resolved incidents retrieved successfully',
                'data' => ['totalResolved' => $resolvedCount]
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
}
