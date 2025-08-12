<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreUBFormService;
use Illuminate\Http\Request;


class IncidentStatusController extends Controller
{

    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'incidentStatuses';

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            $documentRef = FirestoreUBFormService::syncUBFormDocumentAndGetRef($this->collectionName, $data);
            $response = [
                'success' => true,
                'message' => "Incident Status Created Successfully",
                'data' => [
                    'IncidentStatusID' => $documentRef->id()
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

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $IncidentStatusID)
    {
        try {
            $incidentStatus = FirestoreUBFormService::getUBFormDocument($this->collectionName, $IncidentStatusID);
            if ($incidentStatus) {
                $response = [
                    'success' => true,
                    'message' => 'incidentStatus found',
                    'data' => [
                        'incidentStatus' => $incidentStatus
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'incidentStatus not found',
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

    /**
     * Update the specified resource in storage.
     */
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
                    'message' => 'incidentStatus data updated successfully',
                    'data' => $data
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'incidentStatus not found',
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

    //delete
    public function destroy(string $id)
    {
        try {
            $success = FirestoreUBFormService::deleteUBFormDocument($this->collectionName, $id);

            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Incident Status data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Incident Status not found',
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
}
