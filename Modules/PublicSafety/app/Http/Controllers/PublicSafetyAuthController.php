<?php

namespace Modules\PublicSafety\Http\Controllers;

// use App\Http\Controllers\Controller;
use Illuminate\Routing\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Illuminate\Http\Request;
use App\Services\FirestoreService;
// use Illuminate\Http\Request;

class PublicSafetyAuthController extends Controller
{
    /**
     * STEP 1:
     * Redirect the user to Google's OAuth login page.
     *
     * This route MUST be public (no auth middleware),
     * because the user does not have a token yet.
     */
    public function redirect(Request $request)
    {
        // Construct redirect URI dynamically to ensure it matches the actual callback URL
        // This ensures it works across different environments (local, staging, production)
        $redirectUri = config('services.google_public_safety.redirect_uri');

        // If not set in config, construct absolute URL from request
        if (!$redirectUri) {
            $baseUrl = rtrim(config('app.url'), '/');
            // Fallback to request URL if APP_URL is not set
            if ($baseUrl === 'http://localhost:3031') {
                $baseUrl = $request->getSchemeAndHttpHost();
            }
            $redirectUri = $baseUrl . '/auth/google/public-safety-callback';
        }


        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl($redirectUri)
            ->redirect();
    }

    /**
     * STEP 2:
     * Google redirects back to this method after successful login.
     *
     * Handles Postgres-only authorization, auto-assigns roles, 
     * and issues a Sanctum token.
     */
    public function callback(Request $request)
    {
        $googleUser = null;

        try {
            // 1. Fetch authenticated user information from Google
            $redirectUri = config('services.google_public_safety.redirect_uri');

            // If not set in config, construct absolute URL from request
            if (!$redirectUri) {
                $baseUrl = rtrim(config('app.url'), '/');
                // Fallback to request URL if APP_URL is not set
                if ($baseUrl === 'http://localhost') {
                    $baseUrl = $request->getSchemeAndHttpHost();
                }
                $redirectUri = $baseUrl . '/auth/google/public-safety-callback';
            }

            $googleUser = Socialite::driver('google')
                ->stateless()
                ->redirectUrl($redirectUri)
                ->user();

            // 2. Create or update the local user record in Postgres
            $user = User::updateOrCreate(
                ['email' => $googleUser->email],
                [
                    'name' => $googleUser->name,
                    'google_id' => $googleUser->id,
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(),
                ]
            );
            // 3. Ensure user was successfully created/retrieved
            if (!$user || !$user->id) {
                Log::error('User ID is null after creation/retrieval', ['email' => $googleUser->email ?? null]);
                return redirect(config('app.public_safety_frontend') . '?error=user_creation_failed');
            }

            // AUTO-ROLE ASSIGNMENT: If the user has no roles yet and belongs to your domain, grant a default role
            if ($user->roles()->count() === 0 && Str::endsWith($user->email, '@ub.edu.bz')) {
                // Look up the Officer role UUID from your roles table
                $defaultRole = \Modules\Auth\Models\Role::where('role_name', 'api_public_safety_Officer@ub.edu.bz')->first();

                if ($defaultRole) {
                    // This inserts the row into your 'user_roles' pivot table automatically
                    $user->roles()->attach($defaultRole->id);
                } else {
                    Log::error("Failed to assign default role: 'api_public_safety_Officer@ub.edu.bz' not found in database. Did you run the Seeder?");
                }
            }

            // 4. SECURITY CHECK
            if (!$this->isPublicSafetyUser($user)) {
                return redirect(config('app.public_safety_frontend') . '?error=unauthorized');
            }


            // 5. Log in user
            Auth::login($user);

            // 6. Create Sanctum token
            $token = $user->createToken('public-safety-token', ['access-public-safety'])->plainTextToken;

            // 7. Redirect with token directly to the frontend application
            $frontendUrl = rtrim(config('app.public_safety_frontend'), '/');
            return redirect("{$frontendUrl}?token={$token}");
        } catch (\Exception $e) {
            // Log safely using null-coalescing operator
            Log::error('Public Safety OAuth callback error', [
                'email' => $googleUser->email ?? null,
                'error' => $e->getMessage()
            ]);

            return redirect(config('app.public_safety_frontend') . '?error=callback_failed');
        }
    }


    /**
     * Protected endpoint to return the authenticated user's info.
     *
     * Requires:
     * - auth:sanctum middleware
     * - token with "access-public-safety" ability
     */
    public function me(Request $request)
    {
        // Get authenticated user from Sanctum token
        $user = $request->user();

        // Get the token currently being used
        $token = $user->currentAccessToken();

        /**
         * Match ability naming convention to prevent 403 blocks
         */
        if (!$token || !$token->can('access-public-safety')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Return flat structure that satisfies: userData.id && userData.name && userData.email
        return response()->json([
            'id'    => $user->id,
            'email' => $user->email,
            'name'  => $user->name,
            'roles' => $user->roles()->pluck('role_name')
        ]);
    }

    /**
     * GET api/v1/publicSafety/user
     * Aligned with frontend profile properties expectations
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        if (!$token || !$token->can('access-public-safety')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Return with 'user' root key to satisfy: userData.user condition block
        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'status' => $user->status,
                'domain' => $user->domain,
                'email_verified_at' => $user->email_verified_at
            ]
        ]);
    }

    private function isPublicSafetyUser(User $user): bool
    {
        $allowedSystemRoles = [
            'api_public_safety_Admin@ub.edu.bz',
            'api_public_safety_Security@ub.edu.bz',
            'api_public_safety_Officer@ub.edu.bz'
        ];

        // Hits your user_roles table with an ultra-fast SQL EXISTS clause
        return $user->roles()->whereIn('role_name', $allowedSystemRoles)->exists();
    }


    /**
     * Check if the user is allowed into Public Safety.
     *
     * This should:
     *  - Query Google Directory API
     *  - Check membership in Public Safety groups
     */
    // private function isPublicSafetyUser(string $email): bool
    // {
    //     try {
    //         /**
    //          * Initialize Google Client using service account
    //          */
    //         $client = new GoogleClient();
    //         $client->setAuthConfig(config('google.service_account_key_path'));

    //         /**
    //          * Required scopes to read users & group membership
    //          */
    //         $client->addScope([
    //             'https://www.googleapis.com/auth/admin.directory.group.readonly',
    //             'https://www.googleapis.com/auth/admin.directory.user.readonly',
    //         ]);

    //         /**
    //          * IMPORTANT:
    //          * This must be a Google Workspace ADMIN
    //          * (NOT the logged-in user)
    //          */
    //         $client->setSubject('luis.herrera@ub.edu.bz');

    //         /**
    //          * Create Google Directory service
    //          */
    //         $directory = new GoogleDirectory($client);

    //         /**
    //          * Public Safety Google Groups allowed
    //          */
    //         $publicSafetyGroups = [
    //             'api_public_safety@ub.edu.bz',
    //             'api_public_safety_Admin@ub.edu.bz',
    //             'api_public_safety_Security@ub.edu.bz',
    //         ];

    //         /**
    //          * Check group membership
    //          */
    //         foreach ($publicSafetyGroups as $groupEmail) {
    //             try {
    //                 // If user is a member, this call succeeds
    //                 $directory->members->get($groupEmail, $email);
    //                 return true;
    //             } catch (\Exception $e) {
    //                 // Not in this group — continue checking others
    //                 continue;
    //             }
    //         }

    //         // User is not in ANY allowed Public Safety group
    //         return false;

    //     } catch (\Exception $e) {
    //         /**
    //          * If something goes wrong (API error, config issue),
    //          * FAIL CLOSED (deny access).
    //          */
    //         Log::error('Public Safety group check failed', [
    //             'email' => $email,
    //             'error' => $e->getMessage()
    //         ]);

    //         return false;
    //     }
    // }

    /**
     * Create a Firestore profile document for the user.
     *
     * This runs only once per user.
     * It does NOT overwrite existing data.
     */
    private function createPublicSafetyProfile(string $email)
    {
        // Get Firestore instance
        $firestore = FirestoreService::firestore();

        // Reference document using email as the ID
        $doc = $firestore
            ->collection('publicSafetyUsers')
            ->document($email);

        /**
         * If the document does not exist,
         * create it with default values.
         */
        if (!$doc->snapshot()->exists()) {
            $doc->set([
                'email' => $email,
                'created_at' => now(),
                'role' => 'user',
                'permissions' => [],
            ]);
        }
    }
}
