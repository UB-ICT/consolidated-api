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

        // Check if user is in the api_annual_reports Google group
        if (!$this->isUserInAnnualReportsGroup($_user->email)) {
            Log::warning('Google login attempt denied for user: ' . $_user->email . ' - Not in api_annual_reports Google group');
            return redirect(config('app.frontend_url') . '?error=access_denied');
        }

        Auth::login($_user);
        $token = $_user->createToken('google-login')->plainTextToken;

        return redirect(config('app.frontend_url') . '?token=' . $token);
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
    private function getMenusByMailingGroups($mailingGroups)
    {
        try {
            // Get all menus from Firebase using the specific menu method
            $allMenus = FirestoreService::getMenuItems();

            Log::info('All menus: ' . json_encode($allMenus));
            $userMenus = [];

            foreach ($allMenus as $menu) {
                // Check if menu has roles field and if any of user's mailing groups match
                if (isset($menu['roles']) && is_array($menu['roles'])) {
                    foreach ($menu['roles'] as $menuRole) {
                        if (in_array($menuRole, $mailingGroups)) {
                            // Add menu to flat list without role grouping
                            $userMenus[] = [
                                'id' => $menu['id'] ?? null,
                                'name' => $menu['name'] ?? '',
                                'path' => $menu['path'] ?? '',
                                'icon' => $menu['icon'] ?? '',
                                'order' => $menu['order'] ?? 0,
                                'is_active' => $menu['is_active'] ?? true
                            ];
                            break; // Add menu only once even if user is in multiple matching groups
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
    public function getUserInfo(Request $request)
    {
        $user = $request->user();

        if (!$this->isUserInAnnualReportsGroup($user->email)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get user's mailing groups
        $mailingGroups = $this->getUserMailingGroups($user->email);

        // Get menus based on mailing groups
        $menus = $this->getMenusByMailingGroups($mailingGroups);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'google_id' => $user->google_id,
                'role_id' => $user->role_id,
                'campus_id' => $user->campus_id,
                'user_status_id' => $user->user_status_id,
                'profile_picture' => $user->profile_picture,
                'mailing_groups' => $mailingGroups,
                'menus' => $menus
            ]
        ]);
    }
}
