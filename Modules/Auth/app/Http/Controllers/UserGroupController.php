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

class UserGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            // Query the pivot table and join related labels (user/group)
            // so the frontend gets display-ready rows in one call.
            $assignments = DB::connection('pgsql')
                ->table('user_groups as ug')
                ->join('users as u', 'u.id', '=', 'ug.user_id')
                ->join('groups as g', 'g.id', '=', 'ug.group_id')
                ->select([
                    'ug.user_id',
                    'ug.group_id',
                    'u.name as user_name',
                    'u.email as user_email',
                    'g.group_name',
                ])
                ->orderBy('u.name')
                ->orderBy('g.group_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assignments,
                'count' => $assignments->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching user-group assignments: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user-group assignments',
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
            // Validate both FK ids against the Auth/public schema.
            'user_id' => 'required|uuid|exists:pgsql.users,id',
            'group_id' => [
                'required',
                'uuid',
                'exists:pgsql.groups,id',
                // Composite uniqueness: prevent duplicate user-group rows.
                // Equivalent intent: (user_id, group_id) must be unique.
                Rule::unique('pgsql.user_groups', 'group_id')->where(
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

            // Load user model, then attach group through the belongsToMany relation.
            // This writes one row to user_groups.
            $user = User::query()->findOrFail($data['user_id']);
            $user->groups()->attach($data['group_id']);

            // Re-query with joins so response includes friendly names, not only IDs.
            $assignment = DB::connection('pgsql')
                ->table('user_groups as ug')
                ->join('users as u', 'u.id', '=', 'ug.user_id')
                ->join('groups as g', 'g.id', '=', 'ug.group_id')
                ->where('ug.user_id', $data['user_id'])
                ->where('ug.group_id', $data['group_id'])
                ->select([
                    'ug.user_id',
                    'ug.group_id',
                    'u.name as user_name',
                    'u.email as user_email',
                    'g.group_name',
                ])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'User-group assignment created successfully',
                'data' => $assignment,
            ], 201);
        } catch (Exception $e) {
            Log::error('Error creating user-group assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user-group assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show(Request $request, string $userId, ?string $groupId = null): JsonResponse
    {
        // Supports either:
        // - /user-groups/{userId}/{groupId}
        // - /user-groups/{userId}?group_id=...
        $resolvedGroupId = $groupId ?? $request->query('group_id');

        $validator = Validator::make([
            'user_id' => $userId,
            'group_id' => $resolvedGroupId,
        ], [
            'user_id' => 'required|uuid|exists:pgsql.users,id',
            'group_id' => 'nullable|uuid|exists:pgsql.groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if ($resolvedGroupId) {
                // Specific lookup: one composite assignment row.
                $assignment = DB::connection('pgsql')
                    ->table('user_groups as ug')
                    ->join('users as u', 'u.id', '=', 'ug.user_id')
                    ->join('groups as g', 'g.id', '=', 'ug.group_id')
                    ->where('ug.user_id', $userId)
                    ->where('ug.group_id', $resolvedGroupId)
                    ->select([
                        'ug.user_id',
                        'ug.group_id',
                        'u.name as user_name',
                        'u.email as user_email',
                        'g.group_name',
                    ])
                    ->first();

                if (! $assignment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User-group assignment not found',
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'data' => $assignment,
                ]);
            }

            // Collection lookup: user + all linked groups.
            $user = User::with('groups')
                ->findOrFail($userId);

            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching user-group assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user-group assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $userId, ?string $groupId = null): JsonResponse
    {
        // Route param takes precedence; request fallback supports alternate clients.
        $currentGroupId = $groupId ?? $request->input('current_group_id');

        $validator = Validator::make([
            'user_id' => $userId,
            'current_group_id' => $currentGroupId,
            'new_group_id' => $request->input('new_group_id'),
        ], [
            'user_id' => 'required|uuid|exists:pgsql.users,id',
            'current_group_id' => 'required|uuid|exists:pgsql.groups,id',
            'new_group_id' => [
                'required',
                'uuid',
                'different:current_group_id',
                'exists:pgsql.groups,id',
                // Guard against replacing with a group already assigned to this user.
                Rule::unique('pgsql.user_groups', 'group_id')->where(
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

            // Replace flow:
            // 1) delete old (user_id, current_group_id)
            // 2) insert new (user_id, new_group_id)
            $deletedRows = DB::connection('pgsql')
                ->table('user_groups')
                ->where('user_id', $userId)
                ->where('group_id', $data['current_group_id'])
                ->delete();

            if ($deletedRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'User-group assignment not found',
                ], 404);
            }

            $user->groups()->attach($data['new_group_id']);

            $assignment = DB::connection('pgsql')
                ->table('user_groups as ug')
                ->join('users as u', 'u.id', '=', 'ug.user_id')
                ->join('groups as g', 'g.id', '=', 'ug.group_id')
                ->where('ug.user_id', $userId)
                ->where('ug.group_id', $data['new_group_id'])
                ->select([
                    'ug.user_id',
                    'ug.group_id',
                    'u.name as user_name',
                    'u.email as user_email',
                    'g.group_name',
                ])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'User-group assignment updated successfully',
                'data' => $assignment,
            ]);
        } catch (Exception $e) {
            Log::error('Error updating user-group assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user-group assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $userId, ?string $groupId = null): JsonResponse
    {
        // Accept the target group id from route first, then request body.
        $resolvedGroupId = $groupId ?? $request->input('group_id');

        $validator = Validator::make([
            'user_id' => $userId,
            'group_id' => $resolvedGroupId,
        ], [
            'user_id' => 'required|uuid|exists:pgsql.users,id',
            'group_id' => 'required|uuid|exists:pgsql.groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Delete exactly one composite pair.
            $deletedRows = DB::connection('pgsql')
                ->table('user_groups')
                ->where('user_id', $userId)
                ->where('group_id', $resolvedGroupId)
                ->delete();

            if ($deletedRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'User-group assignment not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User-group assignment deleted successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Error deleting user-group assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user-group assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
