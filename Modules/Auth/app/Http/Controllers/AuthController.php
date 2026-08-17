<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
                $user->forceFill(['last_active' => now()])->save();

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
     * Get Active Directory groups for a user using LdapRecord
     */
    private function getUserADGroups($username)
    {
        try {
            // Get the default LDAP connection
            $connection = Container::getDefaultConnection();
            
            // Search for user using sAMAccountName
            $filter = "(sAMAccountName=$username)";
            $baseDN = config('ldap.connections.default.base_dn');
            
            $result = $connection->query()
                ->setDn($baseDN)
                ->rawFilter($filter)
                ->get();
            
            // Check if result is empty
            if (empty($result) || (is_array($result) && count($result) === 0)) {
                return [];
            }
            
            // Get the first user entry (handle both array and object)
            $user = is_array($result) ? $result[0] : $result->first();
            $groups = [];
            
            // Get user's groups from memberOf attribute
            if ($user) {
                // Handle array format (raw LDAP result)
                if (is_array($user) && isset($user['memberof'])) {
                    foreach ($user['memberof'] as $groupDN) {
                        // Extract CN from DN
                        if (preg_match('/CN=([^,]+)/', $groupDN, $matches)) {
                            $groups[] = $matches[1];
                        }
                    }
                }
                // Handle object format (LdapRecord model)
                elseif (is_object($user) && method_exists($user, 'hasAttribute') && $user->hasAttribute('memberof')) {
                    foreach ($user->getAttribute('memberof') as $groupDN) {
                        // Extract CN from DN
                        if (preg_match('/CN=([^,]+)/', $groupDN, $matches)) {
                            $groups[] = $matches[1];
                        }
                    }
                }
            }
            
            return $groups;

        } catch (Exception $e) {
            Log::error('Error getting AD groups: ' . $e->getMessage());
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
