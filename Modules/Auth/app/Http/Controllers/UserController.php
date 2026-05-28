<?php

namespace Modules\Auth\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;

class UserController extends Controller
{

    public function index(): JsonResponse
    {
        try {
            $users = User::with(['groups', 'roles'])
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users,
                'count' => $users->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching Auth users: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:pgsql.users,email',
            'password' => 'sometimes|nullable|string|min:8',
            'domain' => 'sometimes|string|max:255',
            'device_token' => 'sometimes|nullable|string|max:500',
            'status' => 'sometimes|nullable|string',
            'profile_picture' => 'sometimes|nullable|url|max:500',
            'google_id' => 'sometimes|nullable|string|max:255',
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
            $data['password'] = $data['password'] ?? Str::random(16);
            $data['type'] = $data['type'] ?? 'auth';

            $user = User::create($data);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->load(['groups', 'roles']),
            ], 201);
        } catch (Exception $e) {
            Log::error('Error creating Auth user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(User $user): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $user->load(['groups', 'roles']),
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching Auth user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => config('app.debug') ? $e->getMessage() : 'Not found',
            ], 404);
        }
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:pgsql.users,email,' . $user->getKey() . ',id',
            'password' => 'sometimes|nullable|string|min:8',
            'domain' => 'sometimes|string|max:255',
            'device_token' => 'sometimes|nullable|string|max:500',
            'status' => 'sometimes|nullable|string',
            'profile_picture' => 'sometimes|nullable|url|max:500',
            'google_id' => 'sometimes|nullable|string|max:255',
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
            $user->update($data);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user->fresh(['groups', 'roles']),
            ]);
        } catch (Exception $e) {
            Log::error('Error updating Auth user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Error deleting Auth user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}