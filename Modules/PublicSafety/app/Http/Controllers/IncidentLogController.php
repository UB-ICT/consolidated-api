<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class IncidentLogController extends Controller
{
    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'incidentLog';

    public function initialize(Request $request)
    {
        try {
            $defaultReport = [
                'caseNumber' => $this->generateCaseNumber(),
                'date' => '',
                'timeReported' => '',
                'timeOfIncident' => '',
                'location' => '',
                'incidentType' => '',
                'description' => '',
                'personsInvolved' => '',
                'actionTaken' => '',
                'reportedBy' => '',
                'officerSignature' => [],
                'uploadedBy' => $request->user()->name ?? '',
                'formSubmitted' => false,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];

            Log::info('Initializing Incident Log: ', $defaultReport);
        } catch (\Exception $e) {
        }
        $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $defaultReport);
        return array_merge($defaultReport, ['id' => $documentRef->id()]);
    }

    public function index(Request $request)
    {
        try {
            $incidentLog = FirestoreService::getCollection($this->collectionName);
            $response = [
                'success' => true,
                'message' => 'Incident Logs retrieved successfully',
                'data' => $incidentLog
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
                'caseNumber' => 'required|string',
                'date' => 'required|string',
                'timeReported' => 'required|string',
                'timeOfIncident' => 'required|string',
                'location' => 'required|string',
                'incidentType' => 'required|string',
                'description' => 'required|string',
                'personsInvolved' => 'required|string',
                'actionTaken' => 'required|string',
                'reportedBy' => 'required|string',
                'uploadedBy' => 'required|string',
                'officerSignature' => 'required|string',
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
    public function show(Request $request, string $incidentLogID)
    {
        try {
            $incidentLog = FirestoreService::getDocument($this->collectionName, $incidentLogID);
            if ($incidentLog) {
                $response = [
                    'success' => true,
                    'message' => 'incident Log found',
                    'data' => $incidentLog
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Incident Log not found',
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
        return response()->json($incidentLog, 200);
    }

    //update
    public function update(Request $request, string $id)
    {
        try {
            $data = $request->only([
                'caseNumber',
                'date',
                'timeReported',
                'timeOfIncident',
                'location',
                'incidentType',
                'description',
                'personsInvolved',
                'actionTaken',
                'reportedBy',
                'officerSignature',
                'uploadedBy',
                'formSubmitted'
            ]);
            $data['updated_at'] = now()->toDateTimeString(); // Always track update time

            // Update the document in Firestore
            $success = FirestoreService::updateDocument($this->collectionName, $id, $data);

            if ($success) {
                // Fetch the updated document to return
                $updatedReport = FirestoreService::getDocument($this->collectionName, $id);

                $response = [
                    'success' => true,
                    'message' => 'Incident Log updated successfully',
                    'data' => $updatedReport
                ];

                Log::info('Updated Incident Log: ', $updatedReport);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Incident Log not found',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            Log::error('Incident Log update error: ' . $e->getMessage());
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
                    'message' => 'Incident Log data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Incident Log not found',
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
     * Format: INCLOG-YYYYMMDD-0001
     */
    private function generateCaseNumber(): string
    {
        $date = date('Ymd');
        $prefix = "INCLOG-$date-";

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

    public function getTotalIncidentLog()
    {
        try {
            // 1️⃣ Get all incident logs from Firestore
            $incidentLogs = FirestoreService::getCollection($this->collectionName);

            // 2️⃣ Filter only submitted forms
            $submittedLogs = is_array($incidentLogs)
                ? array_filter($incidentLogs, fn($log) => isset($log['formSubmitted']) && $log['formSubmitted'] === true)
                : [];

            // 3️⃣ Count the number of submitted incident logs
            $total = count($submittedLogs);

            // 4️⃣ Prepare response
            $response = [
                'success' => true,
                'message' => 'Total submitted incident logs retrieved successfully',
                'data' => ['total' => $total],
            ];
        } catch (\Exception $e) {
            // 5️⃣ Handle exceptions
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }

        // 6️⃣ Return JSON response
        return response()->json($response, 200);
    }



    public function generateIncidentLogPdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            $incidentLog = FirestoreService::getDocument($this->collectionName, $reportID);
            if (!$incidentLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incident Log not found',
                    'data' => null,
                ], 404);
            }

            // Load the view and pass the incident report data
            $pdf = Pdf::loadView('publicsafety::incidentlog', [
                'incidentLog' => $incidentLog,
                'user' => $user,
                'request' => $request
            ]);

            // Return the generated PDF as a download
            return $pdf->download('incident_log_' . $reportID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getUnsubmittedIncidentLog(Request $request)
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
                    'message' => 'Unsubmitted incident Log found',
                    'data' => $unsubmittedReport,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No unsubmitted incident Log found',
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
