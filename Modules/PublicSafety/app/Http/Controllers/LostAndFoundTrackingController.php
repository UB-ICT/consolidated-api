<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;


class LostAndFoundTrackingController extends Controller
{
    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'lostAndFoundTracking';

    public function initialize(Request $request)
    {
        try {
            $defaultReport = [
                'facilityName' => '',
                'incidentReportStatus' => '',
                'time' => '',
                'todaysDate' => now()->toDateString(),
                'serialNumber' => '',
                'itemDescription' => '',
                'locationFound' => '',
                'roomNo' => '',
                'foundBy' => '',
                'lostAndFoundTrackingFiles' => [],
                'supervisorWhoReceivedItem' => '',
                'dateReturnedToOwner' => '',
                'timeReturnedToOwner' => '',
                'owner' => '',
                'ownerDOB' => '',
                'ownerAddress' => '',
                'ownerIDNumber' => '',
                'ownerTelephone' => '',
                'remarks' => '',
                'returnedToOwnerSignature' => [],
                'ownerAcknowledgementSignature' => [],
                'uploadedBy' => $request->user()->name ?? '',
                'formSubmitted' => false,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];
            Log::info('Initializing Lost and Found Tracking: ', $defaultReport);
        } catch (\Exception $e) {
            Log::error('Error in LostAndFoundTrackingController@initialize: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
        $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $defaultReport);
        return array_merge($defaultReport, ['id' => $documentRef->id()]);
    }

    public function index(Request $request)
    {
        try {
            $lostAndFoundTracking = FirestoreService::getCollection($this->collectionName);
            $response = [
                'success' => true,
                'data' => $lostAndFoundTracking,
                'message' => 'Lost and Found Tracking retrieved successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Error in LostAndFoundTrackingController@index: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
        return response()->json($response);
    }

    //create/store
    public function store(Request $request)
    {
        try {
            $request->validate([
                'facilityName' => 'required|string',
                'incidentReportStatus' => 'required|string',
                'time' => 'nullable|string',
                'todaysDate' => 'required|string',
                'serialNumber' => 'nullable|string',
                'itemDescription' => 'required|string',
                'locationFound' => 'nullable|string',
                'roomNo' => 'nullable|string',
                'foundBy' => 'nullable|string',
                'lostAndFoundTrackingFiles' => 'nullable|array',
                'supervisorWhoReceivedItem' => 'nullable|string',
                'dateReturnedToOwner' => 'nullable|string',
                'timeReturnedToOwner' => 'nullable|string',
                'owner' => 'nullable|string',
                'ownerDOB' => 'nullable|string',
                'ownerAddress' => 'nullable|string',
                'ownerIDNumber' => 'nullable|string',
                'ownerTelephone' => 'nullable|string',
                'remarks' => 'nullable|string',
                'returnedToOwnerSignature' => 'required|string',
                'ownerAcknowledgementSignature' => 'required|string',
                'uploadedBy' => 'required|string',
                'formSubmitted' => 'required|boolean',
            ]);

            $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $request->all());
            // Get the document ID
            $documentId = $documentRef->id();

            // Update the document to include the ID field
            $documentRef->update([
                ['path' => 'id', 'value' => $documentId]
            ]);

            //build lost and found tracking  data for response by merging query data with document id
            $lostAndFoundTracking = $request->all();
            $lostAndFoundTracking['id'] = $documentId;

            $response = [
                'success' => true,
                'data' => $lostAndFoundTracking,
                'message' => 'Lost and Found Tracking created successfully',
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
        return response()->json($response);
    }

    public function show(Request $request, string $lostAndFoundTrackingID)
    {
        try {
            $lostAndFoundTracking = FirestoreService::getDocument($this->collectionName, $lostAndFoundTrackingID);
            if ($lostAndFoundTracking) {
                $response = [
                    'success' => true,
                    'message' => 'Lost and Found Tracking found',
                    'data' => $lostAndFoundTracking
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Lost and Found Tracking not found',
                    'data' => null
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
        return response($response, 200);
    }

    public function update(Request $request, string $id)
    {
        try {
            $data = $request->only(
                [
                    'facilityName',
                    'incidentReportStatus',
                    'time',
                    'todaysDate',
                    'serialNumber',
                    'itemDescription',
                    'locationFound',
                    'roomNo',
                    'foundBy',
                    'lostAndFoundTrackingFiles',
                    'supervisorWhoReceivedItem',
                    'dateReturnedToOwner',
                    'timeReturnedToOwner',
                    'owner',
                    'ownerDOB',
                    'ownerAddress',
                    'ownerIDNumber',
                    'ownerTelephone',
                    'remarks',
                    'returnedToOwnerSignature',
                    'ownerAcknowledgementSignature',
                    'uploadedBy',
                    'formSubmitted',
                ]
            );
            $success = FirestoreService::updateDocument(
                $this->collectionName,
                $id,
                $data
            );
            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Lost data updated successfully',
                    'data' => $data
                ];
                Log::info('Updated Lost and Found Tracking: ', $data);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Lost data not found',
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

    public function destroy(string $id)
    {
        try {
            $success = FirestoreService::deleteDocument($this->collectionName, $id);

            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Lost data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Lost data not found',
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
        //Return response with HTTP status code 201 (Created)
        return response($response, 200);
    }

    public function getTotalLoandFoundTracking(Request $request)
    {
        try {
            // 1️⃣ Get all documents
            $lostAndFoundTracking = FirestoreService::getCollection($this->collectionName);

            $total = 0;

            if (is_array($lostAndFoundTracking)) {
                foreach ($lostAndFoundTracking as $item) {
                    // 2️⃣ Only count submitted forms
                    if (isset($item['formSubmitted']) && $item['formSubmitted'] === true) {
                        $total++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $total,
                'message' => 'Total submitted Lost and Found Tracking retrieved successfully',
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ]);
        }
    }


    public function generateLostAndFoundPdf(Request $request, string $lostAndFoundTrackingID)
    {
        try {
            $user = $request->user();
            $lostAndFoundTracking = FirestoreService::getDocument($this->collectionName, $lostAndFoundTrackingID);
            if (!$lostAndFoundTracking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lost and Found Tracking not found',
                    'data' => null
                ], 404);
            }

            // Load the view and pass the incident report data
            $pdf = Pdf::loadView('publicsafety::lostandfoundtracking', [
                'lostAndFoundTracking' => $lostAndFoundTracking,
                'user' => $user,
                'request' => $request,
            ]);
            // Return the generated PDF as a download
            return $pdf->download('lost_and_found_tracking_' . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getUnsubmittedLostAndFoundTracking(Request $request)
    {
        try {
            $userName = $request->user()->name ?? '';
            $unsubmitted = FirestoreService::getCollectionWhere(
                $this->collectionName,
                'uploadedBy',
                '==',
                $userName
            );

            //filter for reports where submitted == false
            $unsubmittedReport = collect($unsubmitted)
                ->firstWhere('formSubmitted', false);

            if ($unsubmittedReport) {
                return response()->json([
                    'success' => true,
                    'data' => $unsubmittedReport,
                    'message' => 'Unsubmitted Lost and Found Tracking retrieved successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'No unsubmitted Lost and Found Tracking found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in LostAndFoundTrackingController@getUnsubmittedLostAndFoundTracking: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }


    public function getActiveLostAndFoundTracking()
    {
        try {
            // 1️⃣ Get all incident logs from Firestore
            $lostAndFoundTracking = FirestoreService::getCollection($this->collectionName);

            $activeCount = 0;

            if (is_array($lostAndFoundTracking)) {
                foreach ($lostAndFoundTracking as $log) {
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

    public function getResolvedLostAndFoundTracking()
    {
        try {
            // 1️⃣ Get all incident logs from Firestore
            $lostAndFoundTracking = FirestoreService::getCollection($this->collectionName);

            $resolvedCount = 0;

            if (is_array($lostAndFoundTracking)) {
                foreach ($lostAndFoundTracking as $log) {
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

    public function getPendingLostAndFoundTracking()
    {
        try {
            // 1️⃣ Get all incident logs from Firestore
            $incidentLogs = FirestoreService::getCollection($this->collectionName);

            $pendingCount = 0;

            if (is_array($incidentLogs)) {
                foreach ($incidentLogs as $log) {
                    // ✅ Only count submitted forms
                    if (!isset($log['formSubmitted']) || !$log['formSubmitted']) continue;

                    // ✅ Check if incident is "Investigating" or any "active" status
                    if (isset($log['incidentReportStatus']) && $log['incidentReportStatus'] === 'Pending') {
                        $pendingCount++;
                    }
                }
            }

            $response = [
                'success' => true,
                'message' => 'Pending incidents retrieved successfully',
                'data' => ['totalPending' => $pendingCount]
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
