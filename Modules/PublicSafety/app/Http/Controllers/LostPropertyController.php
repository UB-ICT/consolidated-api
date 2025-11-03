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
                'complaintID' => '',
                'complainantEmail' => '',
                'dateLost' => '',
                'timeLost' => '',
                'complaintAffiliation' => '',
                'additionalDescription' => '',
                'owner' => '',
                'ownerSignature' => '',
                'dateReported' => '',

                'dateReturnedToOwner' => '',
                'timeReturnedToOwner' => '',
                'ownerName' => '',
                'ownerDOB' => '',
                'ownerAddress' => '',
                'ownerTelephone' => '',
                'ownerID' => '',
                'remarks' => '',
                'signatureDPS' => '',
                'ownerSignatureReturn' => '',

                'uploadedBy' => $request->user()->email,
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
                'composanintAddress' => 'required|string',
                'complaintDOB' => 'required|date',
                'complainantTelephone' => 'required|string',
                'complaintID' => 'required|string',
                'complainantEmail' => 'required|email',
                'dateLost' => 'required|date',
                'timeLost' => 'required|date',
                'complaintAffiliation' => 'required|string',
                'additionalDescription' => 'required|string',
                'owner' => 'required|string',
                'ownerSignature' => 'required|string',
                'dateReported' => 'required|date',
                'dateReturnedToOwner' => 'required|date',
                'timeReturnedToOwner' => 'required|date',
                'ownerName' => 'required|string',
                'ownerDOB' => 'required|date',
                'ownerAddress' => 'required|string',
                'ownerTelephone' => 'required|string',
                'ownerID' => 'required|string',
                'remarks' => 'required|string',
                'signatureDPS' => 'required|string',
                'ownerSignatureReturn' => 'required|string',

                'uploadedBy' => $request->user()->email,
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
            $data = $request->all();
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

    public function getTotalLostProperty(Request $request)
    {
        try {
            $lostProperty = FirestoreService::getCollection($this->collectionName);

            $total = is_array($lostProperty) ? count($lostProperty) : 0;
            $response = [
                'success' => true,
                'data' => $total,
                'message' => 'Total Lost Property retrieved successfully',
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
            return $pdf->download('lost_property_' . '.pdf');
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

}
