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
use Modules\Auth\Models\Group;

class GroupRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            // Return denormalized assignment rows so clients don't need extra lookups.
            $assignments = DB::connection('pgsql')
                ->table('group_roles as gr')
                ->join('groups as g', 'g.id', '=', 'gr.group_id')
                ->join('roles as r', 'r.id', '=', 'gr.role_id')
                ->select([
                    'gr.group_id',
                    'gr.role_id',
                    'g.group_name',
                    'r.role_name',
                ])
                ->orderBy('g.group_name')
                ->orderBy('r.role_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assignments,
                'count' => $assignments->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching group-role assignments: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch group-role assignments',
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
            'group_id' => 'required|uuid|exists:pgsql.groups,id',
            'role_id' => [
                'required',
                'uuid',
                'exists:pgsql.roles,id',
                // Enforce composite uniqueness: same role cannot be added twice to the same group.
                Rule::unique('pgsql.group_roles', 'role_id')->where(
                    fn ($query) => $query->where('group_id', $request->input('group_id'))
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
            $group = Group::query()->findOrFail($data['group_id']);
            $group->roles()->attach($data['role_id']);

            $assignment = DB::connection('pgsql')
                ->table('group_roles as gr')
                ->join('groups as g', 'g.id', '=', 'gr.group_id')
                ->join('roles as r', 'r.id', '=', 'gr.role_id')
                ->where('gr.group_id', $data['group_id'])
                ->where('gr.role_id', $data['role_id'])
                ->select([
                    'gr.group_id',
                    'gr.role_id',
                    'g.group_name',
                    'r.role_name',
                ])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Group-role assignment created successfully',
                'data' => $assignment,
            ], 201);
        } catch (Exception $e) {
            Log::error('Error creating group-role assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create group-role assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show(Request $request, string $groupId, ?string $roleId = null): JsonResponse
    {
        // Supports either /group-roles/{groupId}/{roleId} or /group-roles/{groupId}?role_id=...
        $resolvedRoleId = $roleId ?? $request->query('role_id');

        $validator = Validator::make([
            'group_id' => $groupId,
            'role_id' => $resolvedRoleId,
        ], [
            'group_id' => 'required|uuid|exists:pgsql.groups,id',
            'role_id' => 'nullable|uuid|exists:pgsql.roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if ($resolvedRoleId) {
                // Fetch a single pivot record for the specific group-role pair.
                $assignment = DB::connection('pgsql')
                    ->table('group_roles as gr')
                    ->join('groups as g', 'g.id', '=', 'gr.group_id')
                    ->join('roles as r', 'r.id', '=', 'gr.role_id')
                    ->where('gr.group_id', $groupId)
                    ->where('gr.role_id', $resolvedRoleId)
                    ->select([
                        'gr.group_id',
                        'gr.role_id',
                        'g.group_name',
                        'r.role_name',
                    ])
                    ->first();

                if (! $assignment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Group-role assignment not found',
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'data' => $assignment,
                ]);
            }

            // Without role_id, return the group with all currently assigned roles.
            $group = Group::with('roles')
                ->findOrFail($groupId);

            return response()->json([
                'success' => true,
                'data' => $group,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching group-role assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch group-role assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $groupId, ?string $roleId = null): JsonResponse
    {
        // Accept role id from route first, then fallback to payload for flexibility.
        $currentRoleId = $roleId ?? $request->input('current_role_id');

        $validator = Validator::make([
            'group_id' => $groupId,
            'current_role_id' => $currentRoleId,
            'new_role_id' => $request->input('new_role_id'),
        ], [
            'group_id' => 'required|uuid|exists:pgsql.groups,id',
            'current_role_id' => 'required|uuid|exists:pgsql.roles,id',
            'new_role_id' => [
                'required',
                'uuid',
                'different:current_role_id',
                'exists:pgsql.roles,id',
                // Prevent replacing with a role already assigned to this group.
                Rule::unique('pgsql.group_roles', 'role_id')->where(
                    fn ($query) => $query->where('group_id', $groupId)
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
            $group = Group::query()->findOrFail($groupId);

            // Replace assignment by deleting old pair then attaching new role.
            $deletedRows = DB::connection('pgsql')
                ->table('group_roles')
                ->where('group_id', $groupId)
                ->where('role_id', $data['current_role_id'])
                ->delete();

            if ($deletedRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group-role assignment not found',
                ], 404);
            }

            $group->roles()->attach($data['new_role_id']);

            $assignment = DB::connection('pgsql')
                ->table('group_roles as gr')
                ->join('groups as g', 'g.id', '=', 'gr.group_id')
                ->join('roles as r', 'r.id', '=', 'gr.role_id')
                ->where('gr.group_id', $groupId)
                ->where('gr.role_id', $data['new_role_id'])
                ->select([
                    'gr.group_id',
                    'gr.role_id',
                    'g.group_name',
                    'r.role_name',
                ])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Group-role assignment updated successfully',
                'data' => $assignment,
            ]);
        } catch (Exception $e) {
            Log::error('Error updating group-role assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update group-role assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $groupId, ?string $roleId = null): JsonResponse
    {
        // Accept role id from route or request body to support multiple client patterns.
        $resolvedRoleId = $roleId ?? $request->input('role_id');

        $validator = Validator::make([
            'group_id' => $groupId,
            'role_id' => $resolvedRoleId,
        ], [
            'group_id' => 'required|uuid|exists:pgsql.groups,id',
            'role_id' => 'required|uuid|exists:pgsql.roles,id',
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
                ->table('group_roles')
                ->where('group_id', $groupId)
                ->where('role_id', $resolvedRoleId)
                ->delete();

            if ($deletedRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group-role assignment not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Group-role assignment deleted successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Error deleting group-role assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete group-role assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
