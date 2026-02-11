<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class BombController extends Controller
{
    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'bombs';

    public function initialize(Request $request)
    {
        try {
            $defaultBomb = [
                'caseNumber' => $this->generateCaseNumber(),
                'date' => '',
                'timeReceived' => '',
                'timeEnded' => '',
                'exactWording' => '',
                'bombLocation' => '',
                'whenWillItGoOff' => '',
                'WhatDoesItLooksLike' => '',
                'whatKindOfBomb' => '',
                'whatWillMakeItExplode' => '',
                'didYouPlaceTheBomb' => '',
                'why' => '',
                'name' => '',
                'payPhone' => '',
                'location' => '',
                'phoneNumber' => '',
                'sex' => '',
                'race' => '',
                'age' => '',
                'callersVoice' => [],
                'backgroundSounds' => [],
                'threatLanguage' => [],
                'accent' => [],
                'additionalInformation' => '',
                'officeNumberReceiveCalls' => '',
                'personReceiveCalls' => '',
                'accentRegion' => '',
                'formSubmitted' => false,
                'uploadedBy' => $request->user()->name ?? '',
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];

            Log::info('Initializing Bomb: ', $defaultBomb);
        } catch (\Exception $e) {
        }
        $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $defaultBomb);
        return array_merge($defaultBomb, ['id' => $documentRef->id()]);
    }

    public function index(Request $request)
    {
        try {
            $bombReport = FirestoreService::getCollection($this->collectionName);
            $response = [
                'success' => true,
                'message' => 'bomb threat retrieved successfully',
                'data' => $bombReport
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
                'timeReceived' => 'required|string',
                'timeEnded' => 'required|string',
                'exactWording' => 'required|string',
                'bombLocation' => 'required|string',
                'whenWillItGoOff' => 'required|string',
                'WhatDoesItLooksLike' => 'required|string',
                'whatKindOfBomb' => 'required|string',
                'whatWillMakeItExplode' => 'required|string',
                'didYouPlaceTheBomb' => 'required|string',
                'why' => 'required|string',
                'name' => 'required|string',
                'payPhone' => 'required|string',
                'location' => 'required|string',
                'phoneNumber' => 'required|string',
                'sex' => 'required|string',
                'race' => 'required|string',
                'age' => 'required|string',
                'callersVoice' => 'required|array',
                'backgroundSounds' => 'required|array',
                'threatLanguage' => 'required|array',
                'accent' => 'required|array',
                'additionalInformation' => 'required|string',
                'officeNumberReceiveCalls' => 'required|string',
                'personReceiveCalls' => 'required|string',
                'accentRegion' => 'required|string',
                'formSubmitted' => 'required|boolean',
                'uploadedBy' => 'required|string',
            ]);

            // Prepare the data to save
            $bombData = $request->all();
            $bombData['created_at'] = now()->toDateTimeString();
            $bombData['updated_at'] = now()->toDateTimeString();

            // Save the document in Firestore and get the reference
            $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $bombData);

            // Add Firestore document ID to the incident data
            $bombData['id'] = $documentRef->id();

            // Update the document to include its ID field
            $documentRef->update([
                ['path' => 'id', 'value' => $bombData['id']]
            ]);

            // Return only the newly created Bomb Threat
            $response = [
                'success' => true,
                'message' => 'Bomb Threat Report Created Successfully',
                'data' => $bombData
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
    public function show(Request $request, string $bombReportID)
    {
        try {
            $bombReport = FirestoreService::getDocument($this->collectionName, $bombReportID);
            if ($bombReport) {
                $response = [
                    'success' => true,
                    'message' => 'Bomb Threat found',
                    'data' => $bombReport
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Bomb Threat not found',
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
        return response()->json($bombReport, 200);
    }

    //update
    public function update(Request $request, string $id)
    {
        try {
            $data = $request->only([
                'caseNumber',
                'date',
                'timeReceived',
                'timeEnded',
                'exactWording',
                'bombLocation',
                'whenWillItGoOff',
                'WhatDoesItLooksLike',
                'whatKindOfBomb',
                'whatWillMakeItExplode',
                'didYouPlaceTheBomb',
                'why',
                'name',
                'payPhone',
                'location',
                'phoneNumber',
                'sex',
                'race',
                'age',
                'description',  // description
                'callersVoice',
                'backgroundSounds',
                'threatLanguage',
                'accent',
                'accentRegion',
                'additionalInformation',
                'officeNumberReceiveCalls',
                'personReceiveCalls',   // personReceiveCalls
                'formSubmitted',
                'uploadedBy',
            ]);
            $data['updated_at'] = now()->toDateTimeString(); // Always track update time

            // Update the document in Firestore
            $success = FirestoreService::updateDocument($this->collectionName, $id, $data);

            if ($success) {
                // Fetch the updated document to return
                $updatedReport = FirestoreService::getDocument($this->collectionName, $id);

                $response = [
                    'success' => true,
                    'message' => 'Bomb threat updated successfully',
                    'data' => $updatedReport
                ];

                Log::info('Updated Bomb threat: ', $updatedReport);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Bomb threat not found',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            Log::error('Bomb threat update error: ' . $e->getMessage());
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
                    'message' => 'bomb Threat data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'bomb Threat not found',
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
     * Generate a sequential case number (Firestore-safe)
     * Format: INC-YYYYMMDD-0001
     */
    private function generateCaseNumber(): string
    {
        $date = date('Ymd');
        $prefix = "BOMB-$date-";

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

    public function getTotalBombReport()
    {
        try {
            // 1️⃣ Get all bomb threat reports from Firestore
            $bombReport = FirestoreService::getCollection($this->collectionName);

            // 2️⃣ Count the number of documents safely
            $total = is_array($bombReport) ? count($bombReport) : 0;

            // 3️⃣ Prepare response
            $response = [
                'success' => true,
                'message' => 'Total Bomb Threats retrieved successfully',
                'data' => ['total' => $total],
            ];
        } catch (\Exception $e) {
            // 4️⃣ Handle exceptions
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }

        // 5️⃣ Return JSON response
        return response()->json($response, 200);
    }

    public function generateBombReportPdf(Request $request, string $reportID)
    {
        try {
            $user = $request->user();
            $bombReport = FirestoreService::getDocument($this->collectionName, $reportID);
            if (!$bombReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bomb Threat not found',
                    'data' => null,
                ], 404);
            }

            // Load the view and pass the incident report data
            $pdf = Pdf::loadView('publicsafety::bombreport', [
                'bombReport' => $bombReport,
                'user' => $user,
                'request' => $request
            ]);

            // Return the generated PDF as a download
            return $pdf->download('bomb_report_' . $reportID . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getUnsubmittedBombReports(Request $request)
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
                    'message' => 'Unsubmitted Bomb report found',
                    'data' => $unsubmittedReport,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No unsubmitted bomb report found',
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
