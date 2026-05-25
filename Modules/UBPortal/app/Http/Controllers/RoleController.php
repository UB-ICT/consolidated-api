<?php

namespace Modules\UBPortal\Http\Controllers;

use Illuminate\Routing\Controller; // Base controller class for Laravel modules
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UBPortal\Models\Permission;
use Modules\UBPortal\Models\Role;

/**
 * Handles CRUD operations for system roles.
 *
 * Roles are used to group permissions and control
 * access across applications/modules.
 */
class RoleController extends Controller
{
    /**
     * Display a list of all roles.
     *
     * Relationships loaded:
     * - permissions
     * - applications
     *
     * Also includes:
     * - permissions_count
     *
     * This helps the frontend display role summaries
     * without needing extra API calls.
     */
    public function index(): JsonResponse
    {
        // Load related permissions and applications
        $roles = Role::with([
                'permissions',
                'applications'
            ])
            // Adds permissions_count attribute automatically
            ->withCount('permissions')

            // Sort roles alphabetically
            ->orderBy('role_name')

            // Retrieve all roles
            ->get();

        return response()->json($roles);
    }

    /**
     * Store a newly created role.
     *
     * Validates incoming role data before saving.
     */
    public function store(Request $request): JsonResponse
    {
        // Validate request payload
        $data = $request->validate([
            // Role name must be unique
            'role_name' => 'required|string|max:255|unique:roles,role_name',

            // Optional role description
            'description' => 'nullable|string',
        ]);

        // Create new role
        $role = Role::create($data);

        // Return created resource with HTTP 201 status
        return response()->json($role, 201);
    }

    /**
     * Display a specific role.
     *
     * Laravel automatically resolves the Role model
     * using route model binding.
     *
     * Related data loaded:
     * - permissions
     * - applications
     * - menuItems
     */
    public function show(Role $role): JsonResponse
    {
        return response()->json(
            $role->load([
                'permissions',
                'applications',
                'menuItems'
            ])
        );
    }

    /**
     * Update an existing role.
     *
     * Allows partial updates using "sometimes".
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        // Validate update payload
        $data = $request->validate([
            /**
             * Role name validation:
             * - optional during updates
             * - must remain unique
             * - ignore current role ID when checking uniqueness
             */
            'role_name' => 'sometimes|required|string|max:255|unique:roles,role_name,' . $role->id,

            // Optional updated description
            'description' => 'nullable|string',
        ]);

        // Update role record
        $role->update($data);

        return response()->json($role);
    }

    /**
     * Delete a role from the system.
     *
     * Be cautious:
     * Deleting a role may affect user access permissions.
     */
    public function destroy(Role $role): JsonResponse
    {
        // Delete role
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Attach permissions to a role without removing existing ones.
     */
    public function attachPermissions(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'permission_ids' => 'required|array|min:1',
            'permission_ids.*' => 'required|uuid|exists:permissions,id',
        ]);

        $role->permissions()->syncWithoutDetaching($data['permission_ids']);

        return response()->json([
            'message' => 'Permissions attached successfully',
            'role' => $role->load('permissions'),
        ]);
    }

    /**
     * Sync permissions for a role, replacing the current set.
     */
    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'required|uuid|exists:permissions,id',
        ]);

        $role->permissions()->sync($data['permission_ids']);

        return response()->json([
            'message' => 'Permissions synced successfully',
            'role' => $role->load('permissions'),
        ]);
    }

    /**
     * Detach a single permission from a role.
     */
    public function detachPermission(Role $role, Permission $permission): JsonResponse
    {
        $role->permissions()->detach($permission->id);

        return response()->json([
            'message' => 'Permission detached successfully',
            'role' => $role->load('permissions'),
        ]);
    }
}