<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class LostPropertyController extends Controller
{
    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'lostProperty';

    public function initialize(Request $request)
    {
        try {
            $defaultReport = [
                'complainantName' => '',
                'complainantAddress' => '',
                'complainantDOB' => '',
                'complainantTelephone' => '',
                'complainantID' => '',
                'complainantEmail' => '',
                'dateLost' => '',
                'timeLost' => '',
                'buildingId' => '',
                'buildingName' => '',
                'latitude' => '',
                'longitude' => '',
                'complainantAffiliation' => '',
                'lostPropertyFiles' => [],
                'additionalDescription' => '',
                'owner' => '',
                'ownerSignature' => [],
                'dateReported' => '',

                'dateReturnedToOwner' => '',
                'timeReturnedToOwner' => '',
                'ownerName' => '',
                'ownerDOB' => '',
                'ownerAddress' => '',
                'ownerTelephone' => '',
                'ownerID' => '',
                'ownerEmail' => '',
                'remarks' => '',
                'incidentReportStatus' => '',
                'signatureDPS' => [],
                'returnedToOwnerSignature' => [],

                'uploadedBy' => $request->user()->name,
                'formSubmitted' => false,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];
            Log::info('Initializing Lost Property: ', $defaultReport);
        } catch (\Exception $e) {
            Log::error('Error in LostPopertyController@initialize: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
        $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $defaultReport);
        return array_merge($defaultReport, ['id' => $documentRef->id()]);
    }

    public function index(Request $request)
    {
        try {
            $lostProperty = FirestoreService::getCollection($this->collectionName);
            $response = [
                'success' => true,
                'data' => $lostProperty,
                'message' => 'Lost Property records retrieved successfully.'
            ];
        } catch (\Exception $e) {
            Log::error('Error in LostPopertyController@index: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
        return response()->json($response, 200);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'complainantName' => 'required|string',
                'compolainantAddress' => 'required|string',
                'complainantDOB' => 'required|date',
                'complainantTelephone' => 'required|string',
                'complainantID' => 'required|string',
                'complainantEmail' => 'required|email',
                'dateLost' => 'required|date',
                'timeLost' => 'required|date',
                'buildingId' => 'required|string',
                'buildingName' => 'required|string',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'complainantAffiliation' => 'required|string',
                'lostPropertyFiles' => 'nullable|array',
                'additionalDescription' => 'nullable|string',
                'owner' => 'required|string',
                'ownerSignature' => 'nullable|array',
                'dateReported' => 'required|date',
                'dateReturnedToOwner' => 'required|date',
                'timeReturnedToOwner' => 'required|date',
                'ownerName' => 'required|string',
                'ownerDOB' => 'required|date',
                'ownerAddress' => 'required|string',
                'ownerTelephone' => 'required|string',
                'ownerID' => 'required|string',
                'remarks' => 'nullable|string',
                'signatureDPS' => 'nullable|array',
                'returnedToOwnerSignature' => 'nullable|array',
                'incidentReportStatus' => 'nullable|string',

                'uploadedBy' => $request->user()->name,
                'formSubmitted' => 'required|boolean',
                'created_at' => 'required|date',
                'updated_at' => 'required|date',

                // Add other validation rules as needed
            ]);
            $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $request->all());
            //Get document ID
            $documentId = $documentRef->id();

            // Update the document to include the ID field
            $documentRef->update([
                ['path' => 'id', 'value' => $documentId]
            ]);

            //build lost and found tracking  data for response by merging query data with document id

            $lostProperty = $request->all();
            $lostProperty['id'] = $documentId;

            $response = [
                'success' => true,
                'data' => $lostProperty,
                'message' => 'Lost Property record created successfully.'
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

    public function show(Request $request, string $lostPropertyID)
    {
        try {
            $lostProperty = FirestoreService::getDocument($this->collectionName, $lostPropertyID);
            if ($lostProperty) {
                $response = [
                    'success' => true,
                    'message' => 'Lost property record retrieved successfully',
                    'data' => $lostProperty
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Lost property record not found',
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
            $data = $request->only([
                'complainantName',
                'complainantAddress',
                'complainantDOB',
                'complainantTelephone',
                'complainantID',
                'complainantEmail',
                'dateLost',
                'timeLost',
                'buildingId',
                'buildingName',
                'latitude',
                'longitude',
                'complainantAffiliation',
                'lostPropertyFiles',
                'additionalDescription',
                'owner',
                'ownerSignature',
                'dateReported',

                'dateReturnedToOwner',
                'timeReturnedToOwner',
                'ownerName',
                'ownerDOB',
                'ownerAddress',
                'ownerTelephone',
                'ownerID',
                'ownerEmail',
                'remarks',
                'signatureDPS',
                'returnedToOwnerSignature',
                'incidentReportStatus',

                'uploadedBy',
                'formSubmitted',
                'created_at',
                'updated_at'
            ]);
            $success = FirestoreService::updateDocument(
                $this->collectionName,
                $id,
                $data
            );
            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Lost property data updated successfully',
                    'data' => $data
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Lost property data not found',
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
                    'message' => 'Lost property data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Lost property data not found',
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

    public function getTotalLostProperty()
    {
        try {
            // 1️⃣ Get all documents
            $lostProperty = FirestoreService::getCollection($this->collectionName);

            $total = 0;

            if (is_array($lostProperty)) {
                foreach ($lostProperty as $item) {

                    // 2️⃣ Only count submitted forms
                    if (isset($item['formSubmitted']) && $item['formSubmitted'] === true) {
                        $total++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Total submitted Lost Property retrieved successfully',
                'data' => [
                    'total' => $total
                ],
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ]);
        }
    }


    public function generateLostPropertyPdf(Request $request, string $lostPropertyID)
    {
        try {
            $user = $request->user();
            $lostProperty = FirestoreService::getDocument($this->collectionName, $lostPropertyID);
            if (!$lostProperty) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lost property not found',
                    'data' => null
                ], 404);
            }

            // Load the view and pass the incident report data
            $pdf = Pdf::loadView('publicsafety::lostproperty', [
                'lostProperty' => $lostProperty,
                'user' => $user,
                'request' => $request,
            ]);
            // Return the generated PDF as a download
            return $pdf->download('lost_property_' . $lostPropertyID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getUnsubmittedLostProperty(Request $request)
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
                    'message' => 'Unsubmitted Lost Property retrieved successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'No unsubmitted Lost Property found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in LostPropertyController@getUnsubmittedLostProperty: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function getActiveLostProperty()
    {
        try {
            // 1️⃣ Get all incident logs from Firestore
            $lostProperty = FirestoreService::getCollection($this->collectionName);

            $activeCount = 0;

            if (is_array($lostProperty)) {
                foreach ($lostProperty as $log) {
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

    public function getResolvedLostProperty()
    {
        try {
            // 1️⃣ Get all incident logs from Firestore
            $lostProperty = FirestoreService::getCollection($this->collectionName);

            $resolvedCount = 0;

            if (is_array($lostProperty)) {
                foreach ($lostProperty as $log) {
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

    public function getPendingLostProperty()
    {
        try {
            // 1️⃣ Get all incident logs from Firestore
            $lostProperty = FirestoreService::getCollection($this->collectionName);

            $pendingCount = 0;

            if (is_array($lostProperty)) {
                foreach ($lostProperty as $log) {
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
