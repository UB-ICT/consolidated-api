<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreUBFormService;

class CampusController extends Controller
{


    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'campuses';


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
                'message' => "campus Created Successfully",
                'data' => [
                    'campusID' => $documentRef->id()
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


    //show/read
    public function show(Request $request, string $campusID)
    {
        try {
            $campus = FirestoreUBFormService::getUBFormDocument($this->collectionName, $campusID);
            if ($campus) {
                $response = [
                    'success' => true,
                    'message' => 'campus found',
                    'data' => [
                        'campus' => $campus
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Campus not found',
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
     * Update.
     */
    public function update(Request $request, string $id)
    {
        try {
            $data = $request->all();
            //add updated timestamp
            $data['updated_at'] = now()->toDateTimeString();
            $success = FirestoreUBFormService::updateUBFormDocument(
                $this->collectionName,
                $id,
                $data
            );
            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Campus data updated successfully',
                    'data' => $data
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Campus not found',
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
                    'message' => 'Campus data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Campus not found',
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
