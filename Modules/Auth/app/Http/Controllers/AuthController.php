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
    public function logout(Request $request)
    {
        try {
            if (!$request->user()) {
                return response([
                    'success' => false,
                    'message' => 'Not authenticated',
                    'data' => null
                ], 401);
            }
            $request->user()->tokens()->delete();
<<<<<<< Updated upstream
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

=======
>>>>>>> Stashed changes
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
