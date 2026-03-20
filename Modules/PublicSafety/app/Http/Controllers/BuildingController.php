<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;

class BuildingController extends Controller
{

    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'buildings';

    public function index(Request $request)
    {
        try {
            $buildings = FirestoreService::getCollection($this->collectionName);
            $response = [
                'success' => true,
                'message' => 'Buildings retrieved successfully',
                'data' => [
                    'buildings' => $buildings,
                ]
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
     * create/store.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            $request->validate([
                'name' => 'required|string',
                'location' => 'required|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);

            // Add timestamps
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();

            // Add to Firestore and get document reference
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
                'message' => "Building Created Successfully",
                'data' => [
                    'buildingID' => $documentId,
                    'building' => $data
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
    public function show(Request $request, string $buildingID)
    {
        try {
            $building = FirestoreService::getDocument($this->collectionName, $buildingID);
            if ($building) {
                $response = [
                    'success' => true,
                    'message' => 'Building found',
                    'data' => [
                        'building' => $building
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Building not found',
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
    // Update building
    public function update(Request $request, string $id)
    {
        try {

            // Validate FIRST
            $validated = $request->validate([
                'name' => 'required|string',
                'location' => 'required|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);

            // Add timestamp
            $validated['updated_at'] = now()->toDateTimeString();

            // Update Firestore
            $success = FirestoreService::updateDocument(
                $this->collectionName,
                $id,
                $validated
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Building not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Building updated successfully',
                'data' => $validated
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
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
