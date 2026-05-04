<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UBPortal\Models\Permission;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        // Grouping by category makes it much easier to build a "Checklist" UI
        $permissions = Permission::orderBy('category')
            ->orderBy('action_name')
            ->get();

        return response()->json($permissions);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => 'required|string|max:255',
            // Added unique check to prevent duplicate permissions like 'user_create'
            'action_name' => 'required|string|max:255|unique:permissions,action_name',
        ]);

        $permission = Permission::create($data);

        return response()->json($permission, 201);
    }

    /**
     * Using Route Model Binding (Permission $permission) 
     * instead of string $id for cleaner code.
     */
    public function show(Permission $permission): JsonResponse
    {
        return response()->json($permission->load('roles'));
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        $data = $request->validate([
            'category' => 'sometimes|required|string|max:255',
            'action_name' => 'sometimes|required|string|max:255|unique:permissions,action_name,' . $permission->id,
        ]);

        $permission->update($data);

        return response()->json($permission);
    }

    public function destroy(Permission $permission): JsonResponse
    {
        // Pro-Tip: You might want to check if this permission is currently 
        // attached to any roles before deleting it to avoid breaking things!
        $permission->delete();

        return response()->json(['message' => 'Permission deleted successfully']);
    }
}
