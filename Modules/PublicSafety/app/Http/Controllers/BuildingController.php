<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreUBFormService;
use Illuminate\Http\Request;

class BuildingController extends Controller
{

    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'buildings';

    /**
     * create/store.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            // Validate required fields including campusId
            $request->validate([
                'name' => 'required|string',
                'location' => 'required|string',
                'campusId' => 'required|string' // Ensure campusId is provided
            ]);

            // Verify the campus exists before creating the building
            $campusExists = FirestoreUBFormService::getUBFormDocument(
                self::COLLECTION_PREFIX . 'campuses',
                $data['campusId']
            );

            if (!$campusExists) {
                throw new \Exception('The specified campus does not exist');
            }
            //add timestamps
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = now()->toDateTimeString();

            $documentRef = FirestoreUBFormService::syncUBFormDocumentAndGetRef($this->collectionName, $data);
            $response = [
                'success' => true,
                'message' => "building Created Successfully",
                'data' => [
                    'buildingID' => $documentRef->id()
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
            $building = FirestoreUBFormService::getUBFormDocument($this->collectionName, $buildingID);
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
                    'message' => 'Building data updated successfully',
                    'data' => $data
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
