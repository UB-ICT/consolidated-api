<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use GPBMetadata\Google\Firestore\V1Beta1\Firestore;
use Illuminate\Http\Request;



class IncidentStatusController extends Controller
{

    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'incidentStatuses';



    public function index()
    {
        try {
            $incidentStatus = FirestoreService::getCollection($this->collectionName);

            $response = [
                'success' => true,
                'message' => "Incident report initialized successfully",
                'data' => $incidentStatus
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
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'incidentStatus' => ['required', 'string', 'max:255'],
        ]);

        try {
            $data = [
                'incidentStatus' => $validated['incidentStatus'],
            ];

            $documentRef = FirestoreService::createIncidentStatus($data);

            return response()->json([
                'id' => $documentRef->id(),
                'incidentStatus' => $data['incidentStatus'], // frontend expects this

            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(string $IncidentStatusID)
    {
        try {
            $incidentStatus = FirestoreService::getDocument($this->collectionName, $IncidentStatusID);
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
            $success = FirestoreService::updateDocument(
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
            $success = FirestoreService::deleteDocument($this->collectionName, $id);

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
