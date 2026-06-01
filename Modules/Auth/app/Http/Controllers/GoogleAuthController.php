<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Google\Client as GoogleClient;
use Google\Service\Directory as GoogleDirectory;
use Exception;
use App\Services\FirestoreService;
use Laravel\Sanctum\Sanctum;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google's OAuth page.
     */
    public function redirect(Request $request)
    {
        $system = $request->get('system'); // "annual" or "public"

        // Get the caller domain from referer or origin
        $callerDomain = $request->header('referer')
            ?? $request->header('origin')
            ?? $request->get('redirect_uri')
            ?? 'https://forms.ub.edu.bz';

        // Extract domain from URL if it's a full URL
        if (filter_var($callerDomain, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($callerDomain);
            $callerDomain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '');
        }

        // Encode system and caller domain in state
        $state = json_encode([
            'system' => $system,
            'domain' => $callerDomain
        ]);

        Log::info('State: ' . $state);
        return Socialite::driver('google')
            ->with(['state' => base64_encode($state)])
            ->redirect();
    }

    public function callback(Request $request)
    {
        $stateParam = request()->get('state');
        $system = 'public'; // default
        $callerDomain = 'https://forms.ub.edu.bz'; // default

        // Decode state if it's base64 encoded JSON
        if ($stateParam) {
            try {
                $decodedState = json_decode(base64_decode($stateParam), true);
                if (is_array($decodedState)) {
                    $system = $decodedState['system'] ?? $system;
                    $callerDomain = $decodedState['domain'] ?? $callerDomain;
                } else {
                    // Fallback: if state is not JSON, treat it as just the system
                    $system = $stateParam;
                }
            } catch (\Exception $e) {
                // If decoding fails, treat state as just the system
                $system = $stateParam;
            }
        }

        // Get Google user
        $user = Socialite::driver('google')->stateless()->user();

        // Retrieve or Create User
        $_user = User::where('email', $user->email)->first();

        if (!$_user) {
            $_user = User::updateOrCreate(
                ['email' => $user->email],
                [
                    'name' => $user->name,
                    'google_id' => $user->id,
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(),
                ]
            );
        } else {
            // Update existing user with google_id if not set
            if (empty($_user->google_id)) {
                $_user->google_id = $user->id;
                $_user->save();
            }
        }

        // Ensure user has an ID before proceeding
        if (!$_user->id) {
            return redirect($callerDomain . '?error=user_creation_failed');
        }

        Auth::login($_user);

        // Default Sanctum ability so token checks behave like a normal PAT (empty [] can confuse tooling).
        $token = $_user->createToken('google-login', ['*'])->plainTextToken;

        $this->instantiateCourseMonitoring($_user->email);
        // Log::info('Token created: ' . $token . ' for user: ' . $_user->email . ' and system: ' . $system);

        $query = http_build_query([
            'token' => $token,
            'system' => $system,
            'email' => $_user->email,
            'name' => $_user->name,
        ]);
        $separator = str_contains($callerDomain, '?') ? '&' : '?';

        return redirect($callerDomain.$separator.$query);
    }

    /**
     * Get user mailing groups from Google Directory API
     */
    private function getUserMailingGroups($email)
    {
        try {
            // Initialize Google Client with service account credentials
            $client = new GoogleClient();
            $client->setAuthConfig(config('google.service_account_key_path'));
            $client->addScope('https://www.googleapis.com/auth/admin.directory.group.readonly');
            $client->addScope('https://www.googleapis.com/auth/admin.directory.user.readonly');
            $client->setSubject('luis.herrera@ub.edu.bz');


            // Create Directory service
            $service = new GoogleDirectory($client);

            // Define relevant groups to check (you can expand this list)
            $relevantGroups = [
                'api_annual_report_Developers@ub.edu.bz',
                'api_annual_report_HR@ub.edu.bz',
                'api_annual_report_Finance@ub.edu.bz',
                'api_annual_report_Records@ub.edu.bz',
                'api_annual_report_Directors@ub.edu.bz',
                'api_annual_report_Admin@ub.edu.bz',
                'api_annual_report_Deans@ub.edu.bz',
                'api_public_safety_Admin@ub.edu.bz', //Chief Public Safety Officer, and Supervisor
                'api_public_safety_Security@ub.edu.bz', //Shift Supervisors
                'api_public_safety_Officer@ub.edu.bz',  //public Safety officers
            ];


            $userGroups = [];

            // Check if user is a member of any relevant group
            foreach ($relevantGroups as $groupEmail) {
                try {
                    $service->members->get($groupEmail, $email);
                    $userGroups[] = $groupEmail;
                } catch (Exception $e) {
                    // User is not a member of this group, continue
                    continue;
                }
            }
            return $userGroups;
        } catch (Exception $e) {
            Log::error('Error getting user mailing groups for ' . $email . ': ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get menus from Firebase based on user mailing groups
     */
    private function getMenusByMailingGroups($mailingGroups, $system)
    {
        try {
            // Get all menus from Firebase using the specific menu method
            $allMenus = FirestoreService::getMenuItems();

            $userMenus = [];
            $skippedBySystem = 0;
            $skippedByRoles = 0;
            $menusWithoutRoles = 0;

            foreach ($allMenus as $menu) {
                // Skip if menu is not for the current system
                // Only skip if system is explicitly set and doesn't match
                // Menus without a system field are considered global and included
                if (isset($menu['system']) && !empty($menu['system']) && $menu['system'] !== $system) {
                    $skippedBySystem++;
                    continue;
                }

                // Check if menu has roles field and if any of user's mailing groups match
                // If menu has no roles field, consider it public and include it
                if (isset($menu['roles']) && is_array($menu['roles']) && !empty($menu['roles'])) {
                    $matched = false;
                    foreach ($menu['roles'] as $menuRole) {
                        if (in_array($menuRole, $mailingGroups)) {
                            $userMenus[] = [
                                'id' => $menu['id'] ?? null,
                                'name' => $menu['name'] ?? '',
                                'path' => $menu['path'] ?? '',
                                'icon' => $menu['icon'] ?? '',
                                'order' => $menu['order'] ?? 0,
                                'is_active' => $menu['is_active'] ?? true
                            ];
                            $matched = true;
                            break;
                        }
                    }
                    if (!$matched) {
                        $skippedByRoles++;
                    }
                } else {
                    // Menu has no roles or empty roles array - include it as public menu
                    $userMenus[] = [
                        'id' => $menu['id'] ?? null,
                        'name' => $menu['name'] ?? '',
                        'path' => $menu['path'] ?? '',
                        'icon' => $menu['icon'] ?? '',
                        'order' => $menu['order'] ?? 0,
                        'is_active' => $menu['is_active'] ?? true
                    ];
                    $menusWithoutRoles++;
                }
            }

            // Sort menus by order field
            usort($userMenus, function ($a, $b) {
                return $a['order'] <=> $b['order'];
            });

            return $userMenus;
        } catch (Exception $e) {
            Log::error('Error getting menus from Firebase: ' . $e->getMessage());
            return [];
        }
    }

    public function getFormsByMailingGroups(array $mailingGroups, string $system)
    {
        try {
            $allForms = FirestoreService::getPublicSafetyFormItems('publicSafety_forms');

            Log::info('All Forms fetched: ' . json_encode($allForms));

            $userForms = [];
            $skippedBySystem = 0;
            $skippedByRoles = 0;
            $formsWithoutRoles = 0;

            foreach ($allForms as $form) {
                // Skip if form is not for the current system
                // Only skip if system is explicitly set and doesn't match
                // Forms without a system field are considered global and included
                if (isset($form['system']) && !empty($form['system']) && $form['system'] !== $system) {
                    $skippedBySystem++;
                    continue;
                }

                // Check if form has roles field and if any of user's mailing groups match
                // If form has no roles field, consider it public and include it
                if (isset($form['roles']) && is_array($form['roles']) && !empty($form['roles'])) {
                    $matched = false;
                    foreach ($form['roles'] as $formRole) {
                        if (in_array($formRole, $mailingGroups)) {
                            $userForms[] = [
                                'id' => $form['id'] ?? null,
                                'name' => $form['name'] ?? '',
                                'is_active' => $form['is_active'] ?? true
                            ];
                            $matched = true;
                            break;
                        }
                    }
                    if (!$matched) {
                        $skippedByRoles++;
                    }
                } else {
                    // Form has no roles or empty roles array - include it as public form
                    $userForms[] = [
                        'id' => $form['id'] ?? null,
                        'name' => $form['name'] ?? '',
                        'is_active' => $form['is_active'] ?? true
                    ];
                    $formsWithoutRoles++;
                }
            }

            return $userForms;
        } catch (Exception $e) {
            Log::error('Error getting forms from Firebase: ' . $e->getMessage());
            return [];
        }
    }

    public function getTablesByMailingGroups(array $mailingGroups, string $system)
    {
        try {
            $allTables = FirestoreService::getPublicSafetyTableItems('publicSafety_tables');

            $userTables = [];
            $skippedBySystem = 0;
            $skippedByRoles = 0;
            $tablesWithoutRoles = 0;

            foreach ($allTables as $table) {
                // Skip if table is not for the current system
                if (isset($table['system']) && !empty($table['system']) && $table['system'] !== $system) {
                    $skippedBySystem++;
                    continue;
                }

                // Check if table has roles field and if any of user's mailing groups match
                if (isset($table['roles']) && is_array($table['roles']) && !empty($table['roles'])) {
                    $matched = false;
                    foreach ($table['roles'] as $tableRole) {
                        if (in_array($tableRole, $mailingGroups)) {
                            $userTables[] = [
                                'id' => $table['id'] ?? null,
                                'name' => $table['name'] ?? '',
                                'is_active' => $table['is_active'] ?? true
                            ];
                            $matched = true;
                            break;
                        }
                    }
                    if (!$matched) {
                        $skippedByRoles++;
                    }
                } else {
                    // Table has no roles or empty roles array - include it as public table
                    $userTables[] = [
                        'id' => $table['id'] ?? null,
                        'name' => $table['name'] ?? '',
                        'is_active' => $table['is_active'] ?? true
                    ];
                    $tablesWithoutRoles++;
                }
            }

            return $userTables;
        } catch (Exception $e) {
            Log::error('Error getting tables from Firebase: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user info from token.
     */
    public function getAnnualReportUserInfo(Request $request)
    {
        $user = auth('sanctum')->user() ?? $request->user('sanctum');

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if token was created for annual reports system
        $token = $user->currentAccessToken();

        $mailingGroups = $this->getUserMailingGroups($user->email);
        $menus = $this->getMenusByMailingGroups($mailingGroups, 'annual-reports');

        Log::info('Annual Report User Info accessed for ' . $user->email . ' with groups: ' . json_encode($mailingGroups));

        return response()->json([
            'user' => $this->formatUserResponse($user, mailingGroups: $mailingGroups, menus: $menus, forms: [], tables: []),
            'menus' => $menus
        ]);
    }


    public function getPublicSafetyUserInfo(Request $request)
    {
        $user = auth('sanctum')->user() ?? $request->user('sanctum');

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check Sanctum token abilities instead of token name
        $token = $user->currentAccessToken();

        $mailingGroups = $this->getUserMailingGroups($user->email);
        $menus = $this->getMenusByMailingGroups($mailingGroups, 'public-safety');
        $forms = $this->getFormsByMailingGroups($mailingGroups, 'public-safety');
        $tables = $this->getTablesByMailingGroups($mailingGroups, 'public-safety');



        return response()->json([
            'user' => $this->formatUserResponse($user, $mailingGroups, $menus, $forms, $tables),
            'menus' => $menus,
            'forms' => $forms,
            'tables' => $tables
        ]);
    }

    /**
     * Current session user for the SPA.
     *
     * Accepts, in order:
     * 1) Sanctum personal access token (`{id}|{plaintext}`) from the server OAuth redirect flow.
     * 2) Google Sign-In **id_token** (OIDC JWT) in `Authorization: Bearer …` — verified with
     *    `services.google.client_id` via Google’s public keys. Not the Google OAuth **access_token**
     *    (opaque string); that cannot be validated as an ID token.
     */
    public function user(Request $request)
    {
        $resolved = $this->resolveUserForApiUserEndpoint($request);
        if ($resolved instanceof \Illuminate\Http\JsonResponse) {
            return $resolved;
        }

        if (! $resolved instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'id' => $resolved->getKey(),
            'name' => $resolved->name,
            'email' => $resolved->email,
            'email_verified_at' => $resolved->email_verified_at,
        ]);
    }

    /**
     * Case-insensitive `Authorization: Bearer …` plus FastCGi/Apache passthrough headers
     * (Laravel's {@see Request::bearerToken()} only matches the literal prefix `Bearer `).
     */
    private function getBearerTokenFromRequest(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if (! is_string($header) || $header === '') {
            $header = $request->server('HTTP_AUTHORIZATION')
                ?? $request->server('REDIRECT_HTTP_AUTHORIZATION');
        }
        if (! is_string($header) || $header === '') {
            return null;
        }

        if (! preg_match('/Bearer\s+(\S+)/i', trim($header), $matches)) {
            return null;
        }

        $token = $matches[1];

        return $token === '' ? null : $token;
    }

    /**
     * Sanctum PAT is `{id}|{plaintext}`. Stock {@see PersonalAccessToken::findToken()} does not trim
     * segments, so whitespace or a literal `%7C` (instead of `|`) makes verification fail.
     */
    private function normalizeSanctumPatPlainText(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return $raw;
        }

        if (! str_contains($raw, '|')) {
            $decoded = rawurldecode($raw);
            if (str_contains($decoded, '|')) {
                $raw = $decoded;
            } elseif (str_contains($raw, '%7C')) {
                $raw = str_replace('%7C', '|', $raw);
            }
        }

        if (! str_contains($raw, '|')) {
            return $raw;
        }

        [$id, $secret] = explode('|', $raw, 2);

        return trim((string) $id).'|'.trim((string) $secret);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    private function findSanctumAccessTokenRow(string $patPlainText)
    {
        $normalized = $this->normalizeSanctumPatPlainText($patPlainText);
        if (! str_contains($normalized, '|')) {
            return null;
        }

        $model = Sanctum::personalAccessTokenModel();
        if ($row = $model::findToken($normalized)) {
            return $row;
        }

        [, $secretPart] = explode('|', $normalized, 2);
        $hash = hash('sha256', $secretPart);

        return $model::query()->where('token', $hash)->first();
    }

    /**
     * @return User|\Illuminate\Http\JsonResponse|null
     */
    private function resolveUserForApiUserEndpoint(Request $request): User|\Illuminate\Http\JsonResponse|null
    {
        $raw = $this->getBearerTokenFromRequest($request);
        if ($raw === null || $raw === '') {
            return null;
        }

        if (str_contains($raw, '|') || str_contains($raw, '%7C')) {
            $accessToken = $this->findSanctumAccessTokenRow($raw);
            if (! $accessToken || ! $accessToken->tokenable instanceof User) {
                return null;
            }

            $expiration = config('sanctum.expiration');
            if ($expiration && $accessToken->created_at && ! $accessToken->created_at->gt(now()->subMinutes($expiration))) {
                return null;
            }
            if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
                return null;
            }

            return $accessToken->tokenable;
        }

        $googlePayload = $this->verifyGoogleIdToken($raw);
        if ($googlePayload === false) {
            return null;
        }

        $email = $googlePayload['email'] ?? null;
        if (! is_string($email) || $email === '') {
            return null;
        }

        if (array_key_exists('email_verified', $googlePayload)) {
            $ev = $googlePayload['email_verified'];
            if ($ev !== true && $ev !== 'true' && $ev !== 1 && $ev !== '1') {
                return null;
            }
        }

        $allowedDomain = config('services.google.domain');
        if (is_string($allowedDomain) && $allowedDomain !== '' && ! str_ends_with(strtolower($email), '@'.strtolower($allowedDomain))) {
            return response()->json([
                'message' => 'This Google account is not allowed for this application.',
            ], 403);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json([
                'message' => 'No application user exists for this Google account. Complete sign-in via the app first.',
            ], 404);
        }

        $googleSub = $googlePayload['sub'] ?? null;
        if (is_string($googleSub) && $googleSub !== '' && (string) $user->google_id !== $googleSub) {
            $user->google_id = $googleSub;
            $user->save();
        }

        return $user;
    }

    /**
     * @return array|false Payload array on success
     */
    private function verifyGoogleIdToken(string $idToken): array|false
    {
        $clientIds = array_values(array_unique(array_filter(array_map(
            static fn ($v) => is_string($v) ? trim($v) : '',
            [
                config('services.google.client_id'),
                config('services.google_annual_report.client_id'),
                config('services.google_public_safety.client_id'),
            ]
        ))));

        if ($clientIds === []) {
            Log::warning('Google ID token verification skipped: no OAuth client_id in services config.');

            return false;
        }

        foreach ($clientIds as $clientId) {
            try {
                $client = new GoogleClient;
                $client->setClientId($clientId);
                $payload = $client->verifyIdToken($idToken);
                if ($payload === false) {
                    continue;
                }

                return is_array($payload) ? $payload : json_decode(json_encode($payload), true);
            } catch (\Throwable $e) {
                Log::debug('Google ID token verification failed for client '.$clientId.': '.$e->getMessage());
            }
        }

        return false;
    }


    private function formatUserResponse($user, $mailingGroups, $menus, $forms, $tables)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'google_id' => $user->google_id,
            'user_status_id' => $user->user_status_id,
            'profile_picture' => $user->profile_picture,
            'mailing_groups' => $mailingGroups,
            'menus' => $menus,
            'forms' => $forms,
            'tables' => $tables
        ];
    }

    //mock google login soo that i can test in postman
    public function mockGoogleLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Mock the Google user
        $_user = User::where('email', $request->email)->first();

        if (!$_user) {
            $_user = User::create([
                'name' => 'Test User',
                'email' => $request->email,
                'google_id' => Str::random(21),
                'password' => bcrypt(Str::random(16)),
                'email_verified_at' => now(),
            ]);
        }

        // Bypass group checks for testing or implement them if needed
        Auth::login($_user);
        $token = $_user->createToken('postman-login')->plainTextToken;

        // // Instantiate courseMonitoring record for the user
        $this->instantiateCourseMonitoring($_user->email);

        return response()->json([
            'token' => $token,
            'user' => $_user
        ]);
    }

    /**
     * Instantiate courseMonitoring record for a user
     * Creates the document if it doesn't exist
     */
    private function instantiateCourseMonitoring(string $email)
    {
        try {
            $firestore = FirestoreService::firestore();
            $collectionName = 'cmon_courseMonitoring';
            $docRef = $firestore->collection($collectionName)->document($email);
            $snapshot = $docRef->snapshot();

            if (!$snapshot->exists()) {
                // Omit `courses` until the first course row exists; readers use courses ?? [].
                $docRef->set([
                    'email' => $email,
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the login process
            Log::error('Error instantiating course evaluation record: ' . $e->getMessage(), [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }
}
