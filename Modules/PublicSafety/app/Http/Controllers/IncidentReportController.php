<?php

namespace Modules\PublicSafety\Http\Controllers;


use Illuminate\Routing\Controller;
use Modules\PublicSafety\Transformers\IncidentReportResource;
use Modules\PublicSafety\Transformers\IncidentReportCollection;
use Modules\PublicSafety\Http\Requests\StoreIncidentReportRequest;
use Modules\PublicSafety\Http\Requests\UpdateIncidentReportRequest;
use Modules\PublicSafety\Models\IncidentReport;
use Modules\PublicSafety\Models\IncidentFile;
use Illuminate\Support\Facades\Storage;
use App\Services\FirestoreUBFormService;
use Illuminate\Http\Request;

class IncidentReportController extends Controller
{

    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'incidentReports';

    //create/store
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            $documentRef = FirestoreUBFormService::syncUBFormDocumentAndGetRef($this->collectionName, $data);
            $response = [
                'success' => true,
                'message' => "incidentReport Created Successfully",
                'data' => [
                    'incidentReportID' => $documentRef->id()
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
