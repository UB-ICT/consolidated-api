<?php

namespace Modules\PublicSafety\Http\Controllers;


use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class IncidentReportController extends Controller
{

    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'incidentReports';

    public function initialize(Request $request)
    {
        try {
            $defaultReport = [
                'action' => '',
                'caseNumber' => $this->generateCaseNumber(),
                'disposition' => '',
                'incidentStatus' => '',
                'incidentType' => '',
                'incidentFiles' => ['incidentFiles' => array(['incidentPicture' => ''])], // Changed from incidentFile to incidentFiles (array)
                'buildingId' => '',
                'buildingLocation' => '',
                'report' => '',
                'uploadedBy' => $request->user()->name ?? '', // Assuming you have authentication
                'date' => "" ,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];

            $response = [
                'success' => true,
                'message' => "Incident report initialized successfully",
                'data' => $defaultReport
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
        $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $defaultReport);
        return array_merge($defaultReport,  ['id' => $documentRef->id()]);

        return response($response, 200);
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

            $data = $request->all();

            $request->validate([
                'action' => 'required|string',
                'caseNumber' => 'nullable|string',
                'disposition' => 'required|string',
                'incidentStatus' => 'required|string',
                'incidentType' => 'required|string',
                'incidentFiles' => 'nullable|array',
                'buildingId' => 'required|string',
                // 'buildingLocation' => 'required|string',
                'report' => 'required|string',
                'uploadedBy' => 'required|string',
            ]);

            // Verify references exist
            $this->verifyReferencesExist($data);

            // Generate case number if not provided
            if (empty($data['caseNumber'])) {
                $data['caseNumber'] = $this->generateCaseNumber();
            }
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $data);
            // Get the document ID
            $documentId = $documentRef->id();

            // Update the document to include the ID field
            $documentRef->update([
                ['path' => 'id', 'value' => $documentId]
            ]);

            // Also add ID to the data array for response
            $data['id'] = $documentId;

            $response = [
                'success' => true,
                'message' => "incidentReport Created Successfully",
                'data' => [
                    'incidentReportID' => $documentRef->id(),
                    'caseNumber' => $data['caseNumber']
                ]
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
                    'data' => [
                        'incident Report' => $incidentReport
                    ]
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
        return response($response, 200);
    }

    //update
    public function update(Request $request, string $id)
    {
        try {
            $data = $request->all();
            // Add updated timestamp
            $data['updated_at'] = now()->toDateTimeString();
            $success = FirestoreService::updateDocument(
                $this->collectionName,
                $id,
                $data
            );
            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'incidentReport data updated successfully',
                    'data' => $data
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'incidentReport not found',
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
     * Generate a unique case number
     */
    private function generateCaseNumber()
    {
        return 'CASE-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }




    public function getTotalIncidentReport()
    {
        try {
            // Get all incident reports from Firestore
            $incidentReports = FirestoreService::getCollection($this->collectionName);

            // Count the number of documents
            $total = is_array($incidentReports) ? count($incidentReports) : 0;

            $response = [
                'success' => true,
                'message' => 'Total incident reports retrieved successfully',
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
}
