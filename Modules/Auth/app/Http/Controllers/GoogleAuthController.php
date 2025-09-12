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

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google's OAuth page.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google.
     */
    public function callback()
    {
        $user = Socialite::driver('google')->user();

        $_user = User::where('email', $user->email)->first();

        if (!$_user) {
            $_user = User::updateOrCreate([
                'name' => $user->name,
                'email' => $user->email,
                'google_id' => $user->id,
                'password' => bcrypt(Str::random(16)),
                'email_verified_at' => now(),
            ]);
        }

        //check which system the user belongs to
        $isAnnualReports = $this->isUserInAnnualReportsGroup($_user->email);
        $isPublicSafety = $this->isUserInPublicSafetyGroup($_user->email);


        if (!$isAnnualReports && !$isPublicSafety) {
            Log::warning('Google login attempt denied for user: ' . $_user->email . ' - Not in any authorized Google group');
            return redirect(config('app.frontend_url') . '?error=access_denied');
        }

        Auth::login($_user);
        if ($isAnnualReports) {
            $tokenName = 'annual-reports-login';
        }

        if ($isPublicSafety) {
            $tokenName = 'public-safety-login';
        }


        $token = $_user->createToken($tokenName)->plainTextToken;
        Log::info('Token name: ' . $tokenName);
        return redirect(config('app.frontend_url') . '?token=' . $token . '&system=' . ($isAnnualReports ? 'annual-reports' : 'public-safety'));
    }

    /**
     * Check if user is in the api_annual_reports Google group
     */
    private function isUserInAnnualReportsGroup($email)
    {
        try {
            // Initialize Google Client with service account credentials
            $client = new GoogleClient();
            $client->setAuthConfig(storage_path('app/google-service-account.json'));
            $client->addScope('https://www.googleapis.com/auth/admin.directory.group.readonly');
            $client->addScope('https://www.googleapis.com/auth/admin.directory.user.readonly');
            $groupEmail = 'api_annual_reports@ub.edu.bz';
            $client->setSubject($email);

            // Create Directory service
            $service = new GoogleDirectory($client);
            // Check if user is a member of the api_annual_reports group

            try {
                $service->members->get($groupEmail, $email);
                return true;
            } catch (Exception $e) {
                Log::error('Error checking Google group membership for user ' . $email . ': ' . $e->getMessage());
                return false;
            }
        } catch (Exception $e) {
            Log::error('Error initializing Google API client: ' . $e->getMessage());
            return false;
        }
    }

    private function isUserInPublicSafetyGroup($email)
    {
        try {
            // Initialize Google Client with service account credentials
            $client = new GoogleClient();
            $client->setAuthConfig(storage_path('app/google-service-account.json'));
            $client->addScope('https://www.googleapis.com/auth/admin.directory.group.readonly');
            $client->addScope('https://www.googleapis.com/auth/admin.directory.user.readonly');
            $groupEmail = 'api_public_safety@ub.edu.bz';
            $client->setSubject($email);

            // Create Directory service
            $service = new GoogleDirectory($client);
            // Check if user is a member of the api_annual_reports group

            try {
                $service->members->get($groupEmail, $email);
                return true;
            } catch (Exception $e) {
                Log::error('Error checking Google group membership for user ' . $email . ': ' . $e->getMessage());
                return false;
            }
        } catch (Exception $e) {
            Log::error('Error initializing Google API client: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user mailing groups from Google Directory API
     */
    private function getUserMailingGroups($email)
    {
        try {
            // Initialize Google Client with service account credentials
            $client = new GoogleClient();
            $client->setAuthConfig(storage_path('app/google-service-account.json'));
            $client->addScope('https://www.googleapis.com/auth/admin.directory.group.readonly');
            $client->addScope('https://www.googleapis.com/auth/admin.directory.user.readonly');
            $client->setSubject($email);

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
                'api_public_safety_Admin@ub.edu.bz',
                'api_public_safety_Security@ub.edu.bz',
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

            Log::info('All menus: ' . json_encode($allMenus));
            $userMenus = [];

            foreach ($allMenus as $menu) {
                // Skip if menu is not for the current system
                if (isset($menu['system']) && $menu['system'] !== $system) {
                    continue;
                }

                // Check if menu has roles field and if any of user's mailing groups match
                if (isset($menu['roles']) && is_array($menu['roles'])) {
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
                            break;
                        }
                    }
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
        if (!str_contains($token->name, 'annual-reports')) {
            return response()->json(['error' => 'Unauthorized for this system'], 401);
        }

        if (!$this->isUserInAnnualReportsGroup($user->email)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $mailingGroups = $this->getUserMailingGroups($user->email);
        $menus = $this->getMenusByMailingGroups($mailingGroups, 'annual-reports');

        return response()->json([
            'user' => $this->formatUserResponse($user, $mailingGroups, $menus)
        ]);
    }


    public function getPublicSafetyUserInfo(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if token was created for public safety system
        $token = $user->currentAccessToken();
        if (!str_contains($token->name, 'public-safety')) {
            return response()->json(['error' => 'Unauthorized for this system'], 401);
        }

        if (!$this->isUserInPublicSafetyGroup($user->email)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $mailingGroups = $this->getUserMailingGroups($user->email);
        $menus = $this->getMenusByMailingGroups($mailingGroups, 'public-safety');

        return response()->json([
            'user' => $this->formatUserResponse($user, $mailingGroups, $menus)
        ]);
    }

    private function formatUserResponse($user, $mailingGroups, $menus)
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
            'menus' => $menus
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

        return response()->json([
            'token' => $token,
            'user' => $_user
        ]);
    }
}
