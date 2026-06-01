<?php

namespace Modules\Auth\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Auth\Models\Role;

class RolePermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            // Return joined names so clients can render labels without additional queries.
            $assignments = DB::connection('pgsql')
                ->table('role_permissions as rp')
                ->join('roles as r', 'r.id', '=', 'rp.role_id')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->select([
                    'rp.role_id',
                    'rp.permission_id',
                    'r.role_name',
                    'p.category',
                    'p.action_name',
                ])
                ->orderBy('r.role_name')
                ->orderBy('p.category')
                ->orderBy('p.action_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assignments,
                'count' => $assignments->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching role-permission assignments: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role-permission assignments',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|uuid|exists:pgsql.roles,id',
            'permission_id' => [
                'required',
                'uuid',
                'exists:pgsql.permissions,id',
                // Composite uniqueness: permission must be unique per role in the pivot table.
                Rule::unique('pgsql.role_permissions', 'permission_id')->where(
                    fn ($query) => $query->where('role_id', $request->input('role_id'))
                ),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $role = Role::query()->findOrFail($data['role_id']);

            // Attach only the validated permission to this role.
            $role->permissions()->attach($data['permission_id']);

            $assignment = DB::connection('pgsql')
                ->table('role_permissions as rp')
                ->join('roles as r', 'r.id', '=', 'rp.role_id')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('rp.role_id', $data['role_id'])
                ->where('rp.permission_id', $data['permission_id'])
                ->select([
                    'rp.role_id',
                    'rp.permission_id',
                    'r.role_name',
                    'p.category',
                    'p.action_name',
                ])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Role-permission assignment created successfully',
                'data' => $assignment,
            ], 201);
        } catch (Exception $e) {
            Log::error('Error creating role-permission assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create role-permission assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show(Request $request, string $roleId, ?string $permissionId = null): JsonResponse
    {
        // Support either route param or query string for flexible API usage.
        $resolvedPermissionId = $permissionId ?? $request->query('permission_id');

        $validator = Validator::make([
            'role_id' => $roleId,
            'permission_id' => $resolvedPermissionId,
        ], [
            'role_id' => 'required|uuid|exists:pgsql.roles,id',
            'permission_id' => 'nullable|uuid|exists:pgsql.permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if ($resolvedPermissionId) {
                // Return one specific role-permission assignment.
                $assignment = DB::connection('pgsql')
                    ->table('role_permissions as rp')
                    ->join('roles as r', 'r.id', '=', 'rp.role_id')
                    ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                    ->where('rp.role_id', $roleId)
                    ->where('rp.permission_id', $resolvedPermissionId)
                    ->select([
                        'rp.role_id',
                        'rp.permission_id',
                        'r.role_name',
                        'p.category',
                        'p.action_name',
                    ])
                    ->first();

                if (! $assignment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Role-permission assignment not found',
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'data' => $assignment,
                ]);
            }

            // Without a permission id, return the role and all assigned permissions.
            $role = Role::with('permissions')
                ->findOrFail($roleId);

            return response()->json([
                'success' => true,
                'data' => $role,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching role-permission assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role-permission assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $roleId, ?string $permissionId = null): JsonResponse
    {
        // Prefer route permission id; fallback allows body-driven clients.
        $currentPermissionId = $permissionId ?? $request->input('current_permission_id');

        $validator = Validator::make([
            'role_id' => $roleId,
            'current_permission_id' => $currentPermissionId,
            'new_permission_id' => $request->input('new_permission_id'),
        ], [
            'role_id' => 'required|uuid|exists:pgsql.roles,id',
            'current_permission_id' => 'required|uuid|exists:pgsql.permissions,id',
            'new_permission_id' => [
                'required',
                'uuid',
                'different:current_permission_id',
                'exists:pgsql.permissions,id',
                // Prevent assigning the same permission twice to one role.
                Rule::unique('pgsql.role_permissions', 'permission_id')->where(
                    fn ($query) => $query->where('role_id', $roleId)
                ),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $role = Role::query()->findOrFail($roleId);

            // Replace pivot row by deleting current pair before attaching the new one.
            $deletedRows = DB::connection('pgsql')
                ->table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $data['current_permission_id'])
                ->delete();

            if ($deletedRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role-permission assignment not found',
                ], 404);
            }

            $role->permissions()->attach($data['new_permission_id']);

            $assignment = DB::connection('pgsql')
                ->table('role_permissions as rp')
                ->join('roles as r', 'r.id', '=', 'rp.role_id')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('rp.role_id', $roleId)
                ->where('rp.permission_id', $data['new_permission_id'])
                ->select([
                    'rp.role_id',
                    'rp.permission_id',
                    'r.role_name',
                    'p.category',
                    'p.action_name',
                ])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Role-permission assignment updated successfully',
                'data' => $assignment,
            ]);
        } catch (Exception $e) {
            Log::error('Error updating role-permission assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update role-permission assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $roleId, ?string $permissionId = null): JsonResponse
    {
        // Accept permission id from route or request body.
        $resolvedPermissionId = $permissionId ?? $request->input('permission_id');

        $validator = Validator::make([
            'role_id' => $roleId,
            'permission_id' => $resolvedPermissionId,
        ], [
            'role_id' => 'required|uuid|exists:pgsql.roles,id',
            'permission_id' => 'required|uuid|exists:pgsql.permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $deletedRows = DB::connection('pgsql')
                ->table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $resolvedPermissionId)
                ->delete();

            if ($deletedRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role-permission assignment not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Role-permission assignment deleted successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Error deleting role-permission assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role-permission assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
