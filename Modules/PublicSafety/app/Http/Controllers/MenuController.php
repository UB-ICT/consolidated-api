<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreService;

class MenuController extends Controller
{

    protected string $collectionName = 'menus';

    //DISPLAY
    public function index()
    {
        try {
            $menus = FirestoreService::getPublicSafetyMenuItems($this->collectionName);
            return response()->json($menus);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //store
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'integer|min:0',
            'is_active' => 'boolean',
            'component' => 'nullable|string',
            'roles' => 'array'
        ]);
        try {
            $documentRef = FirestoreService::createPublicSafetyMenuItem($this->collectionName, $validated);
            return response()->json([
                'id' => $documentRef->id(),
                ...$validated
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'path' => 'sometimes|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'permission' => 'nullable|string'
        ]);
        try {
            $success = FirestoreService::updatePublicSafetyMenuItem($this->collectionName, $id, $validated);
            if ($success) {
                return response()->json(['id' => $id, ...$validated]);
            }
            return response()->json(['error' => 'Menu item not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $success = FirestoreService::deletePublicSafetyMenuItem($this->collectionName, $id);
            if ($success) {
                return response()->json(['message' => 'Menu item deleted']);
            }
            return response()->json(['error' => 'Menu item not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function active()
    {
        try {
            $menus = FirestoreService::getPublicSafetyActiveMenuItems($this->collectionName);
            usort($menus, fn($a, $b) => $a['order'] <=> $b['order']);
            return response()->json($menus);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
