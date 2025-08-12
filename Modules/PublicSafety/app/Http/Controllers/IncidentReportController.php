<?php

namespace Modules\PublicSafety\Http\Controllers;


use Illuminate\Routing\Controller;
use App\Services\FirestoreUBFormService;
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
                'report' => '',
                'disposition' => '',
                'caseNumber' => $this->generateCaseNumber(),
                'action' => '',
                'location' => '',
                'uploadedBy' => $request->user()->id ?? '', // Assuming you have authentication
                'incidentReoccured' => false,
                'frequency' => 0,
                'incidentFiles' => ['pictureURL' => array(['incidentPictures' => ''])], // Changed from incidentFile to incidentFiles (array)
                'incidentStatusId' => '',
                'buildingId' => '',
                'incidentTypeId' => '',
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
        return response($response, 200);
    }


    //create/store
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'report' => 'required|string',
                'disposition' => 'required|string',
                'action' => 'required|string',
                'location' => 'required|string',
                'uploadedBy' => 'required|string',
                'incidentReoccured' => 'required|boolean',
                'frequency' => 'nullable|integer',
                'incidentStatusId' => 'required|string',
                'campusId' => 'required|string',
                'buildingId' => 'required|string',
                'incidentTypeId' => 'required|string',
                'incidentFiles' => 'nullable|array',
                'incidentFiles.*.id' => 'nullable|string',
                'incidentFiles.*.url' => 'required|string'
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $data = $validator->validated();

            // Verify references exist
            $this->verifyReferencesExist($data);

            // Generate case number if not provided
            if (empty($data['caseNumber'])) {
                $data['caseNumber'] = $this->generateCaseNumber();
            }



            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            $documentRef = FirestoreUBFormService::syncUBFormDocumentAndGetRef($this->collectionName, $data);
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
            $incidentReport = FirestoreUBFormService::getUBFormDocument($this->collectionName, $incidentReportID);
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
            $success = FirestoreUBFormService::updateUBFormDocument(
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
            $success = FirestoreUBFormService::deleteUBFormDocument($this->collectionName, $id);

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
                $exists = FirestoreUBFormService::getUBFormDocument($collection, $data[$field]);
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




    // public function getTotalIncidentReport(IncidentReport $incidentReport)
    // {
    //     $incidentReport = IncidentReport::count();
    //     return response()->json(['total' => $incidentReport], 200);
    // }

    // public function uploadIncidentFile(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'incidentId' => 'required|exists:incident_reports,id'
    //     ]);

    //     if ($request->hasFile('file')) {
    //         $file = $request->file('file');
    //         $fileName = time() . '_' . $file->getClientOriginalName();
    //         $filePath = $file->storeAs('incident_files', $fileName, 'public');

    //         $incidentFile = IncidentFile::create([
    //             'incident_report_id' => $request->incidentId,
    //             'path' => Storage::url($filePath),
    //             'name' => $fileName
    //         ]);

    //         return response()->json($incidentFile, 201);
    //     }

    //     return response()->json(['error' => 'File upload failed'], 400);
    // }
}
