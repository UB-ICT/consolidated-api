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
use Modules\Auth\Models\User;

class UserRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            // Join user and role tables so the response includes readable labels,
            // not just raw IDs from the pivot table.
            $assignments = DB::connection('pgsql')
                ->table('user_roles as ur')
                ->join('users as u', 'u.id', '=', 'ur.user_id')
                ->join('roles as r', 'r.id', '=', 'ur.role_id')
                ->select([
                    'ur.user_id',
                    'ur.role_id',
                    'u.name as user_name',
                    'u.email as user_email',
                    'r.role_name',
                ])
                ->orderBy('u.name')
                ->orderBy('r.role_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assignments,
                'count' => $assignments->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching user-role assignments: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user-role assignments',
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
            // Validate both foreign keys against the Auth/public schema.
            'user_id' => 'required|uuid|exists:pgsql.users,id',
            'role_id' => [
                'required',
                'uuid',
                'exists:pgsql.roles,id',
                // Composite uniqueness: each role can be assigned once per user.
                Rule::unique('pgsql.user_roles', 'role_id')->where(
                    fn ($query) => $query->where('user_id', $request->input('user_id'))
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

            // Attach role through Eloquent relation so pivot row is created cleanly.
            $user = User::query()->findOrFail($data['user_id']);
            $user->roles()->attach($data['role_id']);

            // Query back the created row with joined labels.
            $assignment = DB::connection('pgsql')
                ->table('user_roles as ur')
                ->join('users as u', 'u.id', '=', 'ur.user_id')
                ->join('roles as r', 'r.id', '=', 'ur.role_id')
                ->where('ur.user_id', $data['user_id'])
                ->where('ur.role_id', $data['role_id'])
                ->select([
                    'ur.user_id',
                    'ur.role_id',
                    'u.name as user_name',
                    'u.email as user_email',
                    'r.role_name',
                ])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'User-role assignment created successfully',
                'data' => $assignment,
            ], 201);
        } catch (Exception $e) {
            Log::error('Error creating user-role assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user-role assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show(Request $request, string $userId, ?string $roleId = null): JsonResponse
    {
        // Supports either route role id or query string role_id.
        $resolvedRoleId = $roleId ?? $request->query('role_id');

        $validator = Validator::make([
            'user_id' => $userId,
            'role_id' => $resolvedRoleId,
        ], [
            'user_id' => 'required|uuid|exists:pgsql.users,id',
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
                // Return one specific user-role assignment.
                $assignment = DB::connection('pgsql')
                    ->table('user_roles as ur')
                    ->join('users as u', 'u.id', '=', 'ur.user_id')
                    ->join('roles as r', 'r.id', '=', 'ur.role_id')
                    ->where('ur.user_id', $userId)
                    ->where('ur.role_id', $resolvedRoleId)
                    ->select([
                        'ur.user_id',
                        'ur.role_id',
                        'u.name as user_name',
                        'u.email as user_email',
                        'r.role_name',
                    ])
                    ->first();

                if (! $assignment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User-role assignment not found',
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'data' => $assignment,
                ]);
            }

            // Without role_id, return user with all assigned roles.
            $user = User::with('roles')
                ->findOrFail($userId);

            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching user-role assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user-role assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $userId, ?string $roleId = null): JsonResponse
    {
        // Route param has priority; request fallback supports other clients.
        $currentRoleId = $roleId ?? $request->input('current_role_id');

        $validator = Validator::make([
            'user_id' => $userId,
            'current_role_id' => $currentRoleId,
            'new_role_id' => $request->input('new_role_id'),
        ], [
            'user_id' => 'required|uuid|exists:pgsql.users,id',
            'current_role_id' => 'required|uuid|exists:pgsql.roles,id',
            'new_role_id' => [
                'required',
                'uuid',
                'different:current_role_id',
                'exists:pgsql.roles,id',
                // Prevent duplicate role assignment to the same user.
                Rule::unique('pgsql.user_roles', 'role_id')->where(
                    fn ($query) => $query->where('user_id', $userId)
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
            $user = User::query()->findOrFail($userId);

            // Replace existing pivot row:
            // 1) delete current role assignment
            // 2) attach new role assignment
            $deletedRows = DB::connection('pgsql')
                ->table('user_roles')
                ->where('user_id', $userId)
                ->where('role_id', $data['current_role_id'])
                ->delete();

            if ($deletedRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'User-role assignment not found',
                ], 404);
            }

            $user->roles()->attach($data['new_role_id']);

            $assignment = DB::connection('pgsql')
                ->table('user_roles as ur')
                ->join('users as u', 'u.id', '=', 'ur.user_id')
                ->join('roles as r', 'r.id', '=', 'ur.role_id')
                ->where('ur.user_id', $userId)
                ->where('ur.role_id', $data['new_role_id'])
                ->select([
                    'ur.user_id',
                    'ur.role_id',
                    'u.name as user_name',
                    'u.email as user_email',
                    'r.role_name',
                ])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'User-role assignment updated successfully',
                'data' => $assignment,
            ]);
        } catch (Exception $e) {
            Log::error('Error updating user-role assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user-role assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $userId, ?string $roleId = null): JsonResponse
    {
        // Accept role id from route first, then request body fallback.
        $resolvedRoleId = $roleId ?? $request->input('role_id');

        $validator = Validator::make([
            'user_id' => $userId,
            'role_id' => $resolvedRoleId,
        ], [
            'user_id' => 'required|uuid|exists:pgsql.users,id',
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
            // Delete exactly one composite assignment row.
            $deletedRows = DB::connection('pgsql')
                ->table('user_roles')
                ->where('user_id', $userId)
                ->where('role_id', $resolvedRoleId)
                ->delete();

            if ($deletedRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'User-role assignment not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User-role assignment deleted successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Error deleting user-role assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user-role assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
