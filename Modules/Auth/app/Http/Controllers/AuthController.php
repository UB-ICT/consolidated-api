<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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


            if (Auth::validate($credentials)) {
                $user = Auth::getLastAttempted();

             

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

}
