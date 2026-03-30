<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class EndOfShiftReportPatrolController extends Controller
{
    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'endOfShiftReportPatrols';

    public function initialize(Request $request)
    {
        try {
            $defaultReport = [
                'date' => '',
                'time' => '',
                'uploadedBy' => $request->user()->name ?? '', //Patrol Officer
                'campus' => '',
                'endOfShiftReportPatrolFiles' => [],
                'report' => '',
                'formSubmitted' => false,
            ];
        } catch (\Exception $e) {
            Log::info('Initializing End of Shift Report (Patrol Officer)', $defaultReport);
        }
        $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $defaultReport);
        return array_merge($defaultReport, ['id' => $documentRef->id()]);
    }

    public function index()
    {
        try {
            $patrolReports = FirestoreService::getCollection($this->collectionName);
            $response = [
                'success' => true,
                'message' => 'end of shift report retrieved successfully',
                'data' => $patrolReports
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
                'uploadedBy' => 'required|string', //Patrol Officer
                'endOfShiftReportPatrolFiles' => 'nullable|array',
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
            $patrolReport = $request->all();
            $patrolReport['id'] = $documentRef->id(); // add Firestore ID to object

            $response = [
                'success' => true,
                'message' => 'End of Shift Report (Patrol Officer) created successfully',
                'data' => $patrolReport
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
    public function show(string $patrolReportID)
    {
        try {
            $patrolReport = FirestoreService::getDocument($this->collectionName, $patrolReportID);
            if ($patrolReport) {
                $response = [
                    'success' => true,
                    'message' => 'end of shift report found',
                    'data' => $patrolReport
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'End of Shift Report (Patrol Officer) not found',
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
        return response($response, 200);
    }

    //update
    public function update(Request $request, string $id)
    {
        try {
            $data = $request->only([
                'date',
                'time',
                'campus',
                'uploadedBy',
                'report',
                'endOfShiftReportPatrolFiles',
                'formSubmitted',
            ]);
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
                Log::info('Updated End of Shift Report (Patrol Officer): ', $data);
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
                    'message' => 'End of Shift Report (Patrol Officer) data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'End of Shift Report (Patrol Officer) not found',
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


    public function getTotalEndOfShiftReportPatrol()
    {
        try {
            // 1️⃣ Get all patrol end-of-shift reports
            $patrolReports = FirestoreService::getCollection($this->collectionName);

            // 2️⃣ Filter only submitted forms
            $submittedReports = is_array($patrolReports)
                ? array_filter($patrolReports, fn($item) => isset($item['formSubmitted']) && $item['formSubmitted'] === true)
                : [];

            // 3️⃣ Count them
            $total = count($submittedReports);

            // 4️⃣ Respond
            $response = [
                'success' => true,
                'message' => 'Total submitted End of Shift Patrol Reports retrieved successfully',
                'data' => ['total' => $total],
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }

        return response()->json($response, 200);
    }


    public function generateEndOfShiftReportPatrolPdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            $patrolReport = FirestoreService::getDocument($this->collectionName, $reportID);
            if (!$patrolReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'End of Shift Report (Patrol Officer) not found',
                    'data' => null,
                ], 404);
            }

            // Load the view and pass the incident report data
            $pdf = Pdf::loadView('publicsafety::endofshiftreportpatrol', [
                'patrolReport' => $patrolReport,
                'user' => $user,
                'request' => $request
            ]);

            // Return the generated PDF as a download
            return $pdf->download('end_of_shift_report_patrol_' . $reportID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getUnsubmittedEndOfShiftReportPatrols(Request $request)
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
