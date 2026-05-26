<?php

namespace Modules\UBPortal\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\UBPortal\Models\User;

class UserController extends Controller
{
<<<<<<< HEAD
	public function UserCount(): JsonResponse
	{
		try {
			return response()->json([
				'success' => true,
				'total' => User::count(),
			]);
		} catch (Exception $e) {
			Log::error('Error counting UBPortal users: ' . $e->getMessage());

			return response()->json([
				'success' => false,
				'message' => 'Failed to count users',
				'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
			], 500);
		}
	}

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
			Log::error('Error fetching UBPortal users: ' . $e->getMessage());

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
			'email' => 'required|email|unique:porsql.users,email',
			'password' => 'sometimes|nullable|string|min:8',
			'type' => 'sometimes|string|max:255',
			'domain' => 'sometimes|string|max:255',
			'device_token' => 'sometimes|nullable|string|max:500',
			'role_id' => 'sometimes|nullable|string',
			'menu_id' => 'sometimes|nullable|string',
			'campus_id' => 'sometimes|nullable|string',
			'user_status_id' => 'sometimes|nullable|string',
			'profile_picture' => 'sometimes|nullable|url|max:500',
			'google_id' => 'sometimes|nullable|string|max:255',
			'cost_center_id' => 'sometimes|nullable|string',
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
			$data['type'] = $data['type'] ?? 'ubportal';

			$user = User::create($data);

			return response()->json([
				'success' => true,
				'message' => 'User created successfully',
				'data' => $user->load(['groups', 'roles']),
			], 201);
		} catch (Exception $e) {
			Log::error('Error creating UBPortal user: ' . $e->getMessage());

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
			Log::error('Error fetching UBPortal user: ' . $e->getMessage());

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
			'email' => 'sometimes|required|email|unique:porsql.users,email,' . $user->getKey() . ',id',
			'password' => 'sometimes|nullable|string|min:8',
			'type' => 'sometimes|string|max:255',
			'domain' => 'sometimes|string|max:255',
			'device_token' => 'sometimes|nullable|string|max:500',
			'role_id' => 'sometimes|nullable|string',
			'menu_id' => 'sometimes|nullable|string',
			'campus_id' => 'sometimes|nullable|string',
			'user_status_id' => 'sometimes|nullable|string',
			'profile_picture' => 'sometimes|nullable|url|max:500',
			'google_id' => 'sometimes|nullable|string|max:255',
			'cost_center_id' => 'sometimes|nullable|string',
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
			Log::error('Error updating UBPortal user: ' . $e->getMessage());

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
			Log::error('Error deleting UBPortal user: ' . $e->getMessage());

			return response()->json([
				'success' => false,
				'message' => 'Failed to delete user',
				'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
			], 500);
		}
	}
=======
    public function UserCount(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'total' => User::count(),
            ]);
        } catch (Exception $e) {
            Log::error('Error counting UBPortal users: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to count users',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

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
            Log::error('Error fetching UBPortal users: ' . $e->getMessage());

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
            'email' => 'required|email|unique:porsql.users,email',
            'password' => 'sometimes|nullable|string|min:8',
            'type' => 'sometimes|string|max:255',
            'domain' => 'sometimes|string|max:255',
            'device_token' => 'sometimes|nullable|string|max:500',
            'role_id' => 'sometimes|nullable|string',
            'menu_id' => 'sometimes|nullable|string',
            'campus_id' => 'sometimes|nullable|string',
            'user_status_id' => 'sometimes|nullable|string',
            'profile_picture' => 'sometimes|nullable|url|max:500',
            'google_id' => 'sometimes|nullable|string|max:255',
            'cost_center_id' => 'sometimes|nullable|string',
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
            $data['type'] = $data['type'] ?? 'ubportal';

            $user = User::create($data);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->load(['groups', 'roles']),
            ], 201);
        } catch (Exception $e) {
            Log::error('Error creating UBPortal user: ' . $e->getMessage());

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
            Log::error('Error fetching UBPortal user: ' . $e->getMessage());

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
            'email' => 'sometimes|required|email|unique:porsql.users,email,' . $user->getKey() . ',id',
            'password' => 'sometimes|nullable|string|min:8',
            'type' => 'sometimes|string|max:255',
            'domain' => 'sometimes|string|max:255',
            'device_token' => 'sometimes|nullable|string|max:500',
            'role_id' => 'sometimes|nullable|string',
            'menu_id' => 'sometimes|nullable|string',
            'campus_id' => 'sometimes|nullable|string',
            'user_status_id' => 'sometimes|nullable|string',
            'profile_picture' => 'sometimes|nullable|url|max:500',
            'google_id' => 'sometimes|nullable|string|max:255',
            'cost_center_id' => 'sometimes|nullable|string',
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
            Log::error('Error updating UBPortal user: ' . $e->getMessage());

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
            Log::error('Error deleting UBPortal user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
>>>>>>> origin/dev
}
