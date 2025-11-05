<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ImpoundedReportTrackingFormController extends Controller
{
    protected const COLLECTION_PREFIX = 'publicSafety_';

    protected string $collectionName = self::COLLECTION_PREFIX . 'impoundedReportTrackingForms';

    public function initialize(Request $request)
    {
        try {
            $defaultReport = [
                'name' => '',
                'studentID' => '',
                'phoneNumber' => '',
                'address' => '',
                'todayDate' => '',

                //bicycle information form
                'brand' => '',
                'model' => '',
                'color' => '',
                'style' => '',
                'serialNumber' => '',
                'purchaseDate' => '',
                'purchasePrice' => '',
                'locationOfBikeStolen' => '',
                'whatTimeBikeStolen' => '',
                'bicycleRack' => '',
                'whenWasBikeWasStolen' => '',

                //Disposition of property
                'dateReturnedToOwner' => '',
                'ownerName' => '',
                'ownerAddress' => '',
                'ownerDOB' => '',
                'ownerIDNumber' => '',
                'ownerTelephone' => '',
                'remarks' => '',
                'ownerSignature' => '',
                'signaturePSD' => '',

                //Impound Report Tracking Form:
                "nameOfFinder" => "",
                "locationFound" => "",
                "trackingBrand" => "",
                "trackingModel" => "",
                "trackingColor" => "",
                "trackingStyle" => "",
                "trackingSerialNumber" => "",
                "supervisorWhoreceivedItems" => "",
                "trackingFormRemarks" => "",

                //Disposition of property 2
                'dateReturnedToOwner2' => '',
                'ownerName2' => '',
                'ownerAddress2' => '',
                'ownerDOB2' => '',
                'ownerIDNumber2' => '',
                'ownerTelephone2' => '',
                'remarks2' => '',
                'ownerSignature2' => '',
                'signaturePSD2' => '',

                'uploadedBy' => $request->user()->name ?? '', // Assuming you have authentication
                'formSubmitted' => false,

            ];
            Log::info('Initializing Impounded Report Tracking Form: ', $defaultReport);

        } catch (\Exception $e) {
            Log::error('Error in ImpoundedReportTrackingFormController@initialize: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
        $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $defaultReport);
        return array_merge($defaultReport, ['id' => $documentRef->id()]);
    }

    public function index(Request $request)
    {
        try {
            $impoundedReport = FirestoreService::getCollection($this->collectionName);
            $response = [
                'success' => true,
                'data' => $impoundedReport,
                'message' => 'Impounded Report Tracking Form records retrieved successfully.'
            ];
        } catch (\Exception $e) {
            Log::error('Error in ImpoundedReportTrackingFormController@index: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }

        return response()->json($response, 200);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'studentID' => 'required|string',
                'phoneNumber' => 'required|string',
                'address' => 'required|string',
                'todayDate' => 'required|date',

                //bicycle information form
                'brand' => 'required|string',
                'model' => 'required|string',
                'color' => 'required|string',
                'style' => 'required|string',
                'serialNumber' => 'required|string',
                'purchaseDate' => 'nullable|date',
                'purchasePrice' => 'nullable|numeric',
                'locationOfBikeStolen' => 'required|string',
                'whatTimeBikeStolen' => 'required|string',
                'bicycleRack' => 'nullable|string',
                'whenWasBikeWasStolen' => 'required|string',

                //Disposition of property
                'dateReturnedToOwner' => 'nullable|date',
                'ownerName' => 'nullable|string',
                'ownerAddress' => 'nullable|string',
                'ownerDOB' => 'nullable|date',
                'ownerIDNumber' => 'nullable|string',
                'ownerTelephone' => 'nullable|string',
                'remarks' => 'nullable|string',
                'ownerSignature' => 'nullable|string',
                'signaturePSD' => 'nullable|string',


                //Impound Report Tracking Form:
                'nameOfFinder' => 'nullable|string',
                'locationFound' => 'nullable|string',
                'trackingBrand' => 'nullable|string',
                'trackingModel' => 'nullable|string',
                'trackingColor' => 'nullable|string',
                'trackingStyle' => 'nullable|string',
                'trackingSerialNumber' => 'nullable|string',
                'supervisorWhoreceivedItems' => 'nullable|string',
                'trackingFormRemarks' => 'nullable|string',

                //Disposition of property 2
                'dateReturnedToOwner2' => 'nullable|date',
                'ownerName2' => 'nullable|string',
                'ownerAddress2' => 'nullable|string',
                'ownerDOB2' => 'nullable|date',
                'ownerIDNumber2' => 'nullable|string',
                'ownerTelephone2' => 'nullable|string',
                'remarks2' => 'nullable|string',
                'ownerSignature2' => 'nullable|string',
                'signaturePSD2' => 'nullable|string',
                'uploadedBy' => $request->user()->name ?? '', // Assuming you have authentication
                'formSubmitted' => false,
            ]);
            $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $request->all());
            //Get document ID
            $documentId = $documentRef->id();

            // Update the document to include the ID field
            $documentRef->update([
                ['path' => 'id', 'value' => $documentId]
            ]);


            $impoundedReport = $request->all();
            $impoundedReport['id'] = $documentId;

            $response = [
                'success' => true,
                'data' => $impoundedReport,
                'message' => 'impounded report record created successfully.'
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

    public function show(Request $request, string $impoundedReportID)
    {
        try {
            $impoundedReport = FirestoreService::getDocument($this->collectionName, $impoundedReportID);
            if ($impoundedReport) {
                $response = [
                    'success' => true,
                    'message' => 'impounded report record retrieved successfully',
                    'data' => $impoundedReport
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'impounded report record not found',
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
                    'message' => 'IMPOUNDED DATA data updated successfully',
                    'data' => $data
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'IMPOUNDED DATA data not found',
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
                    'message' => 'impounded data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'impounded data not found',
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

    public function getTotalImpoundedReport(Request $request)
    {
        try {
            $impoundedReport = FirestoreService::getCollection($this->collectionName);

            $total = is_array($impoundedReport) ? count($impoundedReport) : 0;
            $response = [
                'success' => true,
                'data' => $total,
                'message' => 'Total impounded report retrieved successfully',
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

    public function generateImpoundedReportPdf(Request $request, string $impoundedReportID)
    {
        try {
            $user = $request->user();
            $impoundedReport = FirestoreService::getDocument($this->collectionName, $impoundedReportID);
            if (!$impoundedReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'impounded report not found',
                    'data' => null
                ], 404);
            }

            // Load the view and pass the incident report data
            $pdf = Pdf::loadView('publicsafety::impoundedreporttracking', [
                'impoundedReport' => $impoundedReport,
                'user' => $user,
                'request' => $request,
            ]);
            // Return the generated PDF as a download
            return $pdf->download('impounded_report_' . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getUnsubmittedImpoundedReport(Request $request)
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
                    'message' => 'Unsubmitted impounded report retrieved successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'No unsubmitted impounded report found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error in ImpoundedReportTrackingController@getUnsubmittedImpoundedReport: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }


}


