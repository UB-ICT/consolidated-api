<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FirestoreService;
use Modules\PublicSafety\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Str;


class UserController extends Controller
{

    /**
     * Display a listing of the users.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $users = User::with(['userStatus'])
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users,
                'count' => $users->count()
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created user (for manual user creation).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'domain' => 'sometimes|string|max:255',
            'user_status_id' => 'sometimes|string',
            'profile_picture' => 'sometimes|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userData = $validator->validated();
            $userData['type'] = $userData['type'] ?? 'public_safety';
            $userData['domain'] = $userData['domain'] ?? 'ub.edu.bz';
            $userData['password'] = bcrypt(Str::random(16)); // Generate random password
            $userData['email_verified_at'] = now();

            $user = User::create($userData);

            // Add to Firestore and get document reference
            $documentRef = FirestoreService::syncDocumentAndGetRef('users', $userData);

            // Sync to Firestore
            $this->syncUserToFirestore($user);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->load(['userStatus'])
            ], 201);
        } catch (Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = User::with(['userStatus'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => config('app.debug') ? $e->getMessage() : 'Not found'
            ], 404);
        }
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'domain' => 'sometimes|string|max:255',
                'device_token' => 'sometimes|string|max:500',
                'user_status_id' => 'sometimes|string',
                'profile_picture' => 'sometimes|url|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update($validator->validated());

            // Sync updated user to Firestore
            $this->syncUserToFirestore($user);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user->fresh(['userStatus'])
            ]);
        } catch (Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            // Delete user from Firestore
            $this->deleteUserFromFirestore($user->id);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Handle Google login and sync user to Firestore
     */
    public function handleGoogleLogin(array $googleUser): User
    {
        try {
            // Find or create user from Google data
            $user = User::firstOrCreate(
                ['email' => $googleUser['email']],
                [
                    'name' => $googleUser['name'],
                    'type' => 'public_safety',
                    'domain' => 'ub.edu.bz',
                    'google_id' => $googleUser['id'],
                    'password' => bcrypt(Str::random(32)),
                    'email_verified_at' => now(),
                    'profile_picture' => $googleUser['avatar'] ?? null,
                ]
            );

            // Update user if they already existed but have new Google data
            if (!$user->wasRecentlyCreated) {
                $updateData = [];
                if (empty($user->google_id)) {
                    $updateData['google_id'] = $googleUser['id'];
                }
                if (empty($user->profile_picture) && isset($googleUser['avatar'])) {
                    $updateData['profile_picture'] = $googleUser['avatar'];
                }

                if (!empty($updateData)) {
                    $user->update($updateData);
                }
            }

            // Sync user to Firestore
            $this->syncUserToFirestore($user);

            Log::info('Google login handled for user: ' . $user->email . ', Firestore sync completed');

            return $user;
        } catch (Exception $e) {
            Log::error('Error handling Google login: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getTotalUsers(User $user)
    {
        $user = User::count();
        return response()->json(['total' => $user]);
    }

    public function getUsers()
    {
        $users = User::select('id', 'name',)->get();

        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * Sync user data to Firestore
     */
    protected function syncUserToFirestore(User $user): bool
    {
        try {
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'domain' => $user->domain,
                'google_id' => $user->google_id,
                'user_status_id' => $user->user_status_id,
                'profile_picture' => $user->profile_picture,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
                'last_login_at' => now()->toISOString(),
            ];

            // Remove null values
            $userData = array_filter($userData, function ($value) {
                return $value !== null;
            });

            // Use updateDocument with merge to create or update
            $result = FirestoreService::updateDocument('users', $user->id, $userData);

            if ($result) {
                Log::info('User synced to Firestore: ' . $user->email);
                return true;
            }

            Log::warning('Failed to sync user to Firestore: ' . $user->email);
            return false;
        } catch (Exception $e) {
            Log::error('Firestore sync error for user ' . $user->email . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete user from Firestore
     */
    protected function deleteUserFromFirestore(string $userId): bool
    {
        try {
            $result = FirestoreService::deleteDocument('users', $userId);

            if ($result) {
                Log::info('User deleted from Firestore: ' . $userId);
                return true;
            }

            Log::warning('Failed to delete user from Firestore: ' . $userId);
            return false;
        } catch (Exception $e) {
            Log::error('Firestore delete error for user ' . $userId . ': ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): JsonResponse
    {
        try {
            $user = User::with(['userStatus'])
                ->where('email', $email)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    /**
     * Update user device token for notifications
     */
    public function updateDeviceToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $user->update(['device_token' => $request->device_token]);

            // Also update in Firestore
            $this->syncUserToFirestore($user);

            return response()->json([
                'success' => true,
                'message' => 'Device token updated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Error updating device token: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update device token'
            ], 500);
        }
    }

    /**
     * Get user profile with relationships
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['userStatus']);

            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching user profile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile'
            ], 500);
        }
    }
}
