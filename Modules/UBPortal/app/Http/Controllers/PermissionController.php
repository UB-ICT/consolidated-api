<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UBPortal\Models\Permission;

/**
 * Handles CRUD operations for system permissions.
 *
 * Permissions define what actions users can perform,
 * usually through role assignments.
 */
class PermissionController extends Controller
{
    /**
     * Display all permissions.
     *
     * Sorted by category then action name for predictable
     * frontend grouping and display.
     */
    public function index(): JsonResponse
    {
        // Order records for stable grouping in UI lists.
        $permissions = Permission::orderBy('category')
            ->orderBy('action_name')
            ->get();

        return response()->json($permissions);
    }

    /**
     * Store a newly created permission.
     *
     * Validates required fields and enforces unique action names.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => 'required|string|max:255',
            // Prevent duplicate permission keys such as "user_create".
            'action_name' => 'required|string|max:255|unique:permissions,action_name',
        ]);

        // Persist the permission record.
        $permission = Permission::create($data);

        return response()->json($permission, 201);
    }

    /**
     * Display a specific permission.
     *
     * Route model binding resolves the Permission instance
     * from the route parameter automatically.
     */
    public function show(Permission $permission): JsonResponse
    {
        // Include related roles so clients can render assignments immediately.
        return response()->json($permission->load('roles'));
    }

    /**
     * Update an existing permission.
     *
     * Uses partial validation so only provided fields are updated.
     */
    public function update(Request $request, Permission $permission): JsonResponse
    {
        $data = $request->validate([
            'category' => 'sometimes|required|string|max:255',
            // Keep action_name unique, excluding the current permission.
            'action_name' => 'sometimes|required|string|max:255|unique:permissions,action_name,' . $permission->id,
        ]);

        // Apply validated updates.
        $permission->update($data);

        return response()->json($permission);
    }

    /**
     * Delete a permission.
     *
     * Removing permissions can affect access checks for roles/users.
     */
    public function destroy(Permission $permission): JsonResponse
    {
        // Delete the permission record.
        $permission->delete();

        return response()->json(['message' => 'Permission deleted successfully']);
    }
}
