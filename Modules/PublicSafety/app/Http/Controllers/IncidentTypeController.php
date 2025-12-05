<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;

class IncidentTypeController extends Controller
{
    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'incidentTypes';




    public function index(Request $request)
    {
        try {
            $incidentTypes = FirestoreService::getCollection($this->collectionName);
            $response = [
                'success' => true,
                'message' => 'Campuses retrieved successfully',
                'data' => [
                    'incidentTypes' => $incidentTypes,
                ]
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

    /**
     * create/store.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();
            $documentRef = FirestoreService::syncDocumentAndGetRef($this->collectionName, $data);
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
            $incidentTypes = FirestoreService::getDocument($this->collectionName, $IncidentTypeID);
            if ($incidentTypes) {
                $response = [
                    'success' => true,
                    'message' => 'incidentType found',
                    'data' => [
                        'incidentTypes' => $incidentTypes
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
            $success = FirestoreService::updateDocument(
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
            $success = FirestoreService::deleteDocument($this->collectionName, $id);

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
