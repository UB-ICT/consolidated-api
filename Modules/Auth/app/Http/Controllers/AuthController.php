<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;
use Exception;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $fields = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
        try {
            $tempDeviceName = 'Temp Device Name';
            $credentials = [
                'samaccountname' => $fields['username'],
                'password' => $fields['password'],
            ];

            if (Auth::attempt($credentials)) {
                $user = Auth::user();

                // Get AD groups for the user
                $adGroups = $this->getUserADGroups($fields['username']);

                $token = $user->createToken($tempDeviceName)->plainTextToken;
                $response = [
                    'success' => true,
                    'message' => "Authenticated Successfully.",
                    'data' => [
                        'token' => $token,
                        'name' => $user->name,
                        'users' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'ad_groups' => $adGroups, // Add AD groups to response
                        ]
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => "The provided credentials are incorrect.",
                    'data' => null,
                ];
            }
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
        return response($response, 200);
    }

    /**
     * Get Active Directory groups for a user
     */
    private function getUserADGroups($username)
    {
        try {
            // LDAP connection settings - update these with your AD server details
            $ldapServer = env('LDAP_HOST', 'ldap://your-ad-server.com');
            $ldapPort = env('LDAP_PORT', 389);
            $ldapUsername = env('LDAP_USERNAME', 'service-account@domain.com');
            $ldapPassword = env('LDAP_PASSWORD', 'password');
            $baseDN = env('LDAP_BASE_DN', 'DC=domain,DC=com');

            // Connect to LDAP
            $ldap = ldap_connect($ldapServer, $ldapPort);
            if (!$ldap) {
                return [];
            }

            // Set LDAP options
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

            // Bind with service account
            if (!ldap_bind($ldap, $ldapUsername, $ldapPassword)) {
                return [];
            }

            // Search for user
            $filter = "(sAMAccountName=$username)";
            $result = ldap_search($ldap, $baseDN, $filter);
            
            if (!$result) {
                return [];
            }

            $entries = ldap_get_entries($ldap, $result);
            if ($entries['count'] == 0) {
                return [];
            }

            $userDN = $entries[0]['dn'];
            $groups = [];

            // Get user's groups
            if (isset($entries[0]['memberof'])) {
                for ($i = 0; $i < $entries[0]['memberof']['count']; $i++) {
                    $groupDN = $entries[0]['memberof'][$i];
                    // Extract CN from DN
                    if (preg_match('/CN=([^,]+)/', $groupDN, $matches)) {
                        $groups[] = $matches[1];
                    }
                }
            }

            ldap_unbind($ldap);
            return $groups;

        } catch (Exception $e) {
            \Log::error('Error getting AD groups: ' . $e->getMessage());
            return [];
        }
    }

    public function logout(Request $request)
    {
        try {
            // Check if user is authenticated
            if (!$request->user()) {
                return response([
                    'success' => false,
                    'message' => 'Not authenticated',
                    'data' => null
                ], 401);
            }

            // Revoke all tokens (logout from all devices)
            $request->user()->tokens()->delete();

            // Alternative: Revoke only the current token (logout from current device)
            // $request->user()->currentAccessToken()->delete();

            $response = [
                'success' => true,
                'message' => 'Successfully logged out',
                'data' => null
            ];
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
        return response($response, 200);
    }
}
