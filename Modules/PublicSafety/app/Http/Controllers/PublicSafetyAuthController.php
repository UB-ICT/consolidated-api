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
use Google\Client as GoogleClient;
use Google\Service\Directory as GoogleDirectory;
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
        $traceId = (string) Str::uuid();

        // Construct redirect URI dynamically to ensure it matches the actual callback URL
        // This ensures it works across different environments (local, staging, production)
        $redirectUri = config('services.google_public_safety.redirect_uri');
        Log::info('Public Safety OAuth redirect started', [
            'trace_id' => $traceId,
            'configured_redirect_uri' => $redirectUri,
            'request_url' => $request->fullUrl(),
            'origin' => $request->headers->get('origin'),
            'referer' => $request->headers->get('referer'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        // If not set in config, construct absolute URL from request
        if (!$redirectUri) {
            $baseUrl = rtrim(config('app.url'), '/');
            // Fallback to request URL if APP_URL is not set
            if ($baseUrl === 'http://localhost:3031') {
                $baseUrl = $request->getSchemeAndHttpHost();
            }
            $redirectUri = $baseUrl . '/auth/google/public-safety-callback';
        }

        Log::info('Public Safety OAuth redirect resolved', [
            'trace_id' => $traceId,
            'resolved_redirect_uri' => $redirectUri,
        ]);
        
        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl($redirectUri)
            ->redirect();
    }

    /**
     * STEP 2:
     * Google redirects back to this method after successful login.
     *
     * This is where we:
     *  - get the Google user
     *  - create or update our local user
     *  - verify Public Safety access
     *  - create a Sanctum token
     *  - initialize Firestore data
     */
    public function callback(Request $request)
    {
        $googleUser = null;
        $traceId = (string) Str::uuid();

        Log::info('Public Safety OAuth callback started', [
            'trace_id' => $traceId,
            'request_url' => $request->fullUrl(),
            'query' => $request->query(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            // 1. Fetch authenticated user information from Google
            // Construct redirect URI dynamically to match what was sent in redirect()
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
           
            Log::info('Public Safety OAuth callback redirect URI resolved', [
                'trace_id' => $traceId,
                'resolved_redirect_uri' => $redirectUri,
            ]);

            $googleUser = Socialite::driver('google')
                ->stateless()
                ->redirectUrl($redirectUri)
                ->user();

            Log::info('Public Safety OAuth Google user received', [
                'trace_id' => $traceId,
                'google_email' => $googleUser->email ?? null,
                'google_id' => $googleUser->id ?? null,
                'google_name' => $googleUser->name ?? null,
            ]);

            // 2. Create or update the local user record
            $user = User::updateOrCreate(
                ['email' => $googleUser->email],
                [
                    'name' => $googleUser->name,
                    'type' => 'public_safety',
                    'domain' => 'ub.edu.bz',
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

            Log::info('Public Safety OAuth local user ready', [
                'trace_id' => $traceId,
                'user_id' => $user->id,
                'email' => $user->email,
                'type' => $user->type,
            ]);

            // 4. SECURITY CHECK
            if (!$this->isPublicSafetyUser($user->email)) {
                Log::warning('Public Safety OAuth group authorization failed', [
                    'trace_id' => $traceId,
                    'email' => $user->email,
                ]);
                return redirect(config('app.public_safety_frontend') . '?error=unauthorized');
            }

            Log::info('Public Safety OAuth group authorization passed', [
                'trace_id' => $traceId,
                'email' => $user->email,
            ]);

            // 5. Log in user
            Auth::login($user);
            $request->session()->regenerate();

            // 6. Create Sanctum token
            $token = $user->createToken('public-safety-token', ['access-public-safety'])->plainTextToken;

            Log::info('Public Safety OAuth token created', [
                'trace_id' => $traceId,
                'email' => $user->email,
                'token_length' => strlen($token),
                'token_preview' => substr($token, 0, 6) . '...' . substr($token, -4),
            ]);

            // 7. Initialize Firestore (non-blocking — failure must not prevent login)
            try {
                $this->createPublicSafetyProfile($user->email);
            } catch (\Exception $e) {
                Log::warning('Firestore profile creation failed (non-fatal)', [
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }

            // 8. Redirect with token payload that matches the working auth flow
            $frontendUrl = rtrim(config('app.public_safety_frontend'), '/');
            $query = http_build_query([
                'token' => $token,
                'access_token' => $token,
                'system' => 'public-safety',
            ]);

            Log::info('Public Safety OAuth callback success redirect', [
                'trace_id' => $traceId,
                'frontend_url' => $frontendUrl,
                'query_keys' => ['token', 'access_token', 'system'],
            ]);

            return redirect("{$frontendUrl}?{$query}");

        } catch (\Exception $e) {
            // Log safely, using null-coalescing operator
            Log::error('Public Safety OAuth callback error', [
                'trace_id' => $traceId,
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
     *  - auth:sanctum middleware
        *  - token with "access-public-safety" ability
     */
    public function me(Request $request)
    {
        $traceId = (string) Str::uuid();

        // Get authenticated user from Sanctum token
        $user = $request->user();

        if (!$user) {
            Log::warning('Public Safety me access denied: missing authenticated user', [
                'trace_id' => $traceId,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get the token currently being used
        $token = $user->currentAccessToken();

        /**
         * Ensure token belongs to the Public Safety system.
         * Prevents token reuse across apps.
         */
        if (!$token || !in_array('access-public-safety', $token->abilities)) {
            Log::warning('Public Safety me access denied: invalid token abilities', [
                'trace_id' => $traceId,
                'email' => $user->email,
                'abilities' => $token?->abilities,
            ]);
            return response()->json(['error' => 'Forbidden'], 403);
        }

        Log::info('Public Safety me access granted', [
            'trace_id' => $traceId,
            'email' => $user->email,
            'token_name' => $token->name,
        ]);

        // Return minimal user profile
        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }


    /**
     * Check if the user is allowed into Public Safety.
     *
     * This should:
     *  - Query Google Directory API
     *  - Check membership in Public Safety groups
     */
    private function isPublicSafetyUser(string $email): bool
    {
        try {
            /**
             * Initialize Google Client using service account
             */
            $serviceAccountPath = storage_path('app/google-service-account.json');
            if (!file_exists($serviceAccountPath)) {
                $serviceAccountPath = storage_path('firebase-credentials.json');
            }

            if (!file_exists($serviceAccountPath)) {
                Log::error('Public Safety group check failed: service account file missing', [
                    'email' => $email,
                    'checked_primary' => storage_path('app/google-service-account.json'),
                    'checked_fallback' => storage_path('firebase-credentials.json'),
                ]);
                return false;
            }

            $serviceAccountMeta = json_decode((string) file_get_contents($serviceAccountPath), true) ?: [];

            $client = new GoogleClient();
            $client->setAuthConfig($serviceAccountPath);

            /**
             * Required scopes to read users & group membership
             */
            $client->addScope([
                'https://www.googleapis.com/auth/admin.directory.group.readonly',
                'https://www.googleapis.com/auth/admin.directory.user.readonly',
            ]);

            /**
             * IMPORTANT:
             * This must be a Google Workspace ADMIN
             * (NOT the logged-in user)
             */
            $client->setSubject('luis.herrera@ub.edu.bz');

            Log::info('Public Safety group check using service account', [
                'email' => $email,
                'service_account_path' => $serviceAccountPath,
                'service_account_client_email' => $serviceAccountMeta['client_email'] ?? null,
                'delegated_subject' => 'luis.herrera@ub.edu.bz',
            ]);

            /**
             * Create Google Directory service
             */
            $directory = new GoogleDirectory($client);

            /**
             * Public Safety Google Groups allowed
             */
            $publicSafetyGroups = [
                'api_public_safety@ub.edu.bz',
                'api_public_safety_admin@ub.edu.bz',
                'api_public_safety_security@ub.edu.bz',
                'api_public_safety_officers@ub.edu.bz',
            ];

            /**
             * Check group membership
             */
            foreach ($publicSafetyGroups as $groupEmail) {
                $normalizedGroupEmail = strtolower($groupEmail);
                try {
                    // If user is a member, this call succeeds
                    $directory->members->get($normalizedGroupEmail, strtolower($email));
                    Log::info('Public Safety group membership matched', [
                        'email' => $email,
                        'group' => $normalizedGroupEmail,
                    ]);
                    return true;
                } catch (\Exception $e) {
                    // Not in this group — continue checking others
                    Log::debug('Public Safety group membership not found in group', [
                        'email' => $email,
                        'group' => $normalizedGroupEmail,
                        'reason' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // User is not in ANY allowed Public Safety group
            Log::warning('Public Safety group authorization failed for all groups', [
                'email' => $email,
                'checked_groups' => $publicSafetyGroups,
            ]);
            return false;

        } catch (\Exception $e) {
            /**
             * If something goes wrong (API error, config issue),
             * FAIL CLOSED (deny access).
             */
            $errorMessage = $e->getMessage();

            if (str_contains($errorMessage, 'invalid_grant') || str_contains($errorMessage, 'Invalid signature for token')) {
                Log::error('Public Safety group check credential signing failure', [
                    'email' => $email,
                    'error' => $errorMessage,
                    'hint' => 'Service account private key and client_email likely do not match, or key is stale/rotated.',
                ]);
            }

            Log::error('Public Safety group check failed', [
                'email' => $email,
                'error' => $errorMessage
            ]);

            return false;
        }
    }

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
