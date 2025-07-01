<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;

class MenuController extends Controller
{
    protected $database;

    public function index()
    {
        try {
            $menus = FirestoreService::getMenuItems();
            return response()->json($menus);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'integer|min:0',
            'is_active' => 'boolean',
            'permission' => 'nullable|string'
        ]);
        try {
            $documentRef = FirestoreService::createMenuItem($validated);
            return response()->json([
                'id' => $documentRef->id(),
                ...$validated
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

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
            $success = FirestoreService::updateMenuItem($id, $validated);
            if ($success) {
                return response()->json(['id' => $id, ...$validated]);
            }
            return response()->json(['error' => 'Menu item not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $success = FirestoreService::deleteMenuItem($id);
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
            $menus = FirestoreService::getActiveMenuItems();
            usort($menus, fn($a, $b) => $a['order'] <=> $b['order']);
            return response()->json($menus);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
