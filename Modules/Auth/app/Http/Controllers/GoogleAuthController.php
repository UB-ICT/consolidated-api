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
use Modules\UBPortal\Models\MenuItem;

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

    public function callback()
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

        // Restrict access to UB email addresses only
        if (!str_ends_with($user->email, '@ub.edu.bz')) {
            return redirect($callerDomain . '?error=unauthorized_domain');
        }

        // Retrieve or Create User
        $_user = User::where('email', $user->email)->first();

        if (!$_user) {
            $_user = User::updateOrCreate(
                ['email' => $user->email],
                [
                    'name' => $user->name,
                    'type' => $system,
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

        // Determine allowed abilities
        $abilities = [];
        // Create ONE token with multiple abilities
        $token = $_user->createToken('google-login', $abilities)->plainTextToken;

        $this->instantiateCourseMonitoring($_user->email);
        Log::info('Token created: ' . $token . ' for user: ' . $_user->email . ' and system: ' . $system);

        // 10. Build response redirect using caller domain
        return redirect($callerDomain . '?token=' . $token . '&access_token=' . $token . '&system=' . $system);
    }

    /**
     * Get user mailing groups from Google Directory API
     */
    private function getUserMailingGroups($email)
    {
        try {
            $serviceAccountPath = storage_path('app/google-service-account.json');
            if (!file_exists($serviceAccountPath)) {
                $serviceAccountPath = storage_path('firebase-credentials.json');
            }

            if (!file_exists($serviceAccountPath)) {
                Log::error('Google Directory service account file missing', [
                    'checked_primary' => storage_path('app/google-service-account.json'),
                    'checked_fallback' => storage_path('firebase-credentials.json'),
                ]);
                return [];
            }

            // Initialize Google Client with service account credentials
            $client = new GoogleClient();
            $client->setAuthConfig($serviceAccountPath);
            $client->addScope('https://www.googleapis.com/auth/admin.directory.group.readonly');
            $client->addScope('https://www.googleapis.com/auth/admin.directory.user.readonly');
            $client->setSubject('luis.herrera@ub.edu.bz');


            // Create Directory service
            $service = new GoogleDirectory($client);

            $relevantGroups = array_flip([
                'api_annual_report_developers@ub.edu.bz',
                'api_annual_report_hr@ub.edu.bz',
                'api_annual_report_finance@ub.edu.bz',
                'api_annual_report_records@ub.edu.bz',
                'api_annual_report_directors@ub.edu.bz',
                'api_annual_report_admin@ub.edu.bz',
                'api_annual_report_deans@ub.edu.bz',
                'api_public_safety_admin@ub.edu.bz',
                'api_public_safety_security@ub.edu.bz',
                'api_public_safety_officer@ub.edu.bz',
            ]);

            // One API call per page to list user groups, then filter locally.
            $userGroups = [];
            $pageToken = null;

            do {
                $params = [
                    'userKey' => $email,
                    'maxResults' => 200,
                ];

                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $result = $service->groups->listGroups($params);

                foreach ($result->getGroups() ?? [] as $group) {
                    $groupEmail = strtolower($group->getEmail());
                    if (isset($relevantGroups[$groupEmail])) {
                        $userGroups[] = $groupEmail;
                    }
                }

                $pageToken = $result->getNextPageToken();
            } while ($pageToken);

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
        $user = $request->user();

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
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check Sanctum token abilities instead of token name
        // $token = $user->currentAccessToken();

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
     * Get UBPortal user info from token.
     *
     * Returns the authenticated user's profile along with their
     * effective roles, permissions, and visible menu items.
     * Roles and permissions are resolved from both direct assignments
     * and group memberships.
     */
    public function getUbPortalUserInfo(Request $request)
    {
        $user = $request->user();

        // Reject unauthenticated requests
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Eager-load roles (with permissions) and groups (with their roles and permissions)
        // to avoid N+1 queries when resolving the effective access set
        $user->load([
            'roles.permissions',
            'groups.roles.permissions',
        ]);

        // Roles assigned directly to the user
        $directRoles = $user->roles;

        // Roles inherited through the user's group memberships
        $groupRoles = $user->groups->flatMap(function ($group) {
            return $group->roles;
        });

        // Merge direct and group roles, removing duplicates by role ID
        $effectiveRoles = $directRoles
            ->concat($groupRoles)
            ->unique('id')
            ->values();

        // Collect all permissions from effective roles, deduplicated by permission ID
        $effectivePermissions = $effectiveRoles
            ->flatMap(function ($role) {
                return $role->permissions;
            })
            ->unique('id')
            ->values();

        // Extract role IDs to use for menu filtering
        $roleIds = $effectiveRoles->pluck('id')->all();

        // Fetch top-level menu items (no parent) that are either:
        // - public (no role_id set), or
        // - restricted to one of the user's effective roles
        $menus = MenuItem::query()
            ->whereNull('parent_id')
            ->where(function ($query) use ($roleIds) {
                $query->whereNull('role_id');

                if (!empty($roleIds)) {
                    $query->orWhereIn('role_id', $roleIds);
                }
            })
            // Eagerly load children with the same role-based visibility logic
            ->with([
                'children' => function ($query) use ($roleIds) {
                    $query->where(function ($childQuery) use ($roleIds) {
                        $childQuery->whereNull('role_id');

                        if (!empty($roleIds)) {
                            $childQuery->orWhereIn('role_id', $roleIds);
                        }
                    })->orderBy('sort_order');
                }
            ])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'user' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'type'        => $user->type,
                'groups'      => $user->groups,
                'roles'       => $effectiveRoles,
                'permissions' => $effectivePermissions,
            ],
            'menus' => $menus,
        ]);
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
                'name' => $request->name ?? 'Mock User',
                'email' => $request->email,
                'type' => 'mock',
                'google_id' => Str::random(21),
                'password' => bcrypt(Str::random(16)),
                'email_verified_at' => now(),
            ]);
        }

        // Bypass group checks for testing or implement them if needed
        Auth::login($_user);
        $token = $_user->createToken('postman-login')->plainTextToken;

        // Instantiate courseMonitoring record for the user
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
                // Create document with empty courses array
                $docRef->set([
                    'email' => $email,
                    'courses' => []
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
