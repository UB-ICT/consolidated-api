<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Routing\Controller; // Standard for Modules
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UBPortal\Models\Role;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        // Eager load counts as well so the UI knows how many permissions exist without loading the whole list
        $roles = Role::with(['permissions', 'applications'])
            ->withCount('permissions')
            ->orderBy('role_name')
            ->get();

        return response()->json($roles);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'role_name' => 'required|string|max:255|unique:roles,role_name',
            'description' => 'nullable|string',
        ]);

        $role = Role::create($data);

        return response()->json($role, 201);
    }

    /**
     * By using (Role $role), Laravel automatically does findOrFail using the UUID
     */
    public function show(Role $role): JsonResponse
    {
        return response()->json($role->load(['permissions', 'applications', 'menuItems']));
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'role_name' => 'sometimes|required|string|max:255|unique:roles,role_name,' . $role->id,
            'description' => 'nullable|string',
        ]);

        $role->update($data);

        return response()->json($role);
    }

    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }
}
