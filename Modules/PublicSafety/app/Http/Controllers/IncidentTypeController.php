<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreUBFormService;
use Illuminate\Http\Request;

class IncidentTypeController extends Controller
{


    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'incidentTypes';


    /**
     * create/store.
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
                'message' => "Incident Type Created Successfully",
                'data' => [
                    'IncidentTypeID' => $documentRef->id()
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
    public function show(Request $request, string $IncidentTypeID)
    {
        try {
            $incidentType = FirestoreUBFormService::getUBFormDocument($this->collectionName, $IncidentTypeID);
            if ($incidentType) {
                $response = [
                    'success' => true,
                    'message' => 'incidentType found',
                    'data' => [
                        'incidentType' => $incidentType
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'incidentType not found',
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
                    'message' => 'Incident Type data updated successfully',
                    'data' => $data
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Incident Type not found',
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
                    'message' => 'Building data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Building not found',
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
