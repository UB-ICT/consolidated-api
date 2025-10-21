<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class EndOfShiftReportSupervisorController extends Controller
{
    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'endOfShiftReportSupervisor';

    public function initialize(Request $request)
    {
        try {
            $defaultReport = [
                'date' => '',
                'time' => '',
                'uploadedBy' => $request->user()->name ?? '', //Supervisor Officer
                'campus' => '',
                'report' => '',
                'formSubmitted' => false,
            ];
        } catch (\Exception $e) {
            Log::info('Initializing End of Shift Report (Shift Supervisor)', $defaultReport);
        }
        $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $defaultReport);
        return array_merge($defaultReport, ['id' => $documentRef->id()]);
    }

    public function index(Request $request)
    {
        try {
            $supervisorReports = FirestoreService::getCollection($this->collectionName);
            $response = [
                'success' => true,
                'message' => 'end of shift report retrieved successfully',
                'data' => $supervisorReports
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
            $request->validate([
                'date' => 'required|string',
                'time' => 'required|string',
                'uploadedBy' => 'required|string',
                'campus' => 'required|string',
                'report' => 'required|string',
                'formSubmitted' => 'required|boolean',
            ]);

            $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $request->all());

            // Get the document ID
            $documentId = $documentRef->id();

            // Update the document to include the ID field
            $documentRef->update([
                ['path' => 'id', 'value' => $documentId]
            ]);

            //build end of shift report data for response by merging request data with the new genereated id
            $supervisorReport = $request->all();
            // $supervisorReport['id'] = $documentRef->id(); // add Firestore ID to object

            $response = [
                'success' => true,
                'message' => 'End of Shift Report ( Supervisor ) created successfully',
                'data' => $supervisorReport
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
    public function show(Request $request, string $supervisorReport)
    {
        try {
            $supervisorReport = FirestoreService::getDocument($this->collectionName, $supervisorReport);
            if ($supervisorReport) {
                $response = [
                    'success' => true,
                    'message' => 'end of shift report found',
                    'data' => $supervisorReport
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'End of Shift Report (Supervisor) not found',
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
        return response($response, 200);
    }


    //update
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
                    'message' => 'end of shift report data updated successfully',
                    'data' => $data
                ];
                Log::info('Updated End of Shift Report (Supervisor): ', $data);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'end of shift report not found',
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
                    'message' => 'End of Shift Report (Supervisor) data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'End of Shift Report (Supervisor) not found',
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

    public function getTotalEndOfShiftReportSupervisor(Request $request)
    {
        try {
            //Get all all end of shift reports from firestore
            $supervisorReport = FirestoreService::getCollection($this->collectionName);

            // Count the number of documents
            $total = is_array($supervisorReport) ? count($supervisorReport) : 0;

            $response = [
                'success' => true,
                'message' => 'Total end of shift report retrieved successfully',
                'data' => ['total' => $total]
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

    public function generateEndOfShiftReportSupervisorPdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            $supervisorReport = FirestoreService::getDocument($this->collectionName, $reportID);
            if (!$supervisorReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'End of Shift Report (supervisor) not found',
                    'data' => null,
                ], 404);
            }

            // Load the view and pass the incident report data
            $pdf = Pdf::loadView('publicsafety::endofshiftreportsupervisor', [
                'supervisorReport' => $supervisorReport,
                'user' => $user,
                'request' => $request
            ]);

            // Return the generated PDF as a download
            return $pdf->download('end_of_shift_report_supervisor_' . $reportID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getUnsubmittedEndOfShiftReportsupervisor(Request $request)
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
                    'message' => 'Unsubmitted end of shift report found',
                    'data' => $unsubmittedReport,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No unsubmitted end of shift report found',
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
